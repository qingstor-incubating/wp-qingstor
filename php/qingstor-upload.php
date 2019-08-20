<?php
define('QS_INVALID_BUCKET_URL', 31);
final class QingStorUpload
{
    private static $instance;
    public function __construct()
    {
        add_action('add_attachment', array($this, 'add_attachment'));
        add_action('qingstor_scheduled_upload_hook', array($this, 'upload_files'));
        add_action('delete_local_file', array($this, 'delete_local_file'));
        add_action('delete_attachment', array($this, 'delete_attachment'));
        add_filter('upload_dir', array($this, 'upload_dir'));
        add_filter('intermediate_image_sizes_advanced', array($this, 'intermediate_image_sizes_advanced'));
    }
    public static function get_instance()
    {
        if (!(self::$instance instanceof QingStorUpload)) {
            self::$instance = new QingStorUpload();
        }
        return self::$instance;
    }
    /**
     * Hook function, auto Sync to QingStor Bucket when upload Media files to WordPress.
     * @param string $post_ID
     */
    public function add_attachment($post_ID)
    {
        $wp_upload_dir = wp_get_upload_dir();
        $attach_url = wp_get_attachment_url($post_ID);
        $file_path = $wp_upload_dir['basedir'] . str_replace($wp_upload_dir['baseurl'], "", $attach_url);
        $data = array('file' => end(explode($wp_upload_dir['basedir'] . '/', $file_path)));
        $this->upload_data($data);
    }
    /**
     * Hook function, delete the file on QingStor Bucket.
     * @param string $post_id
     */
    public function delete_attachment($post_id)
    {
        $options = get_option('qingstor-options');
        $attach_url = wp_get_attachment_url($post_id);
        $wp_upload_dir = wp_get_upload_dir();
        $bucket = qingstor_get_bucket();
        if (empty($bucket)) {
            return;
        }
        $filename = str_replace($wp_upload_dir['baseurl'] . '/', "", $attach_url);
        $object = $options['upload_prefix'] . $filename;
        $bucket->deleteObject($object);
    }
    public function get_files_local_and_remote($dirname, $basedir, $prefix)
    {
        $dir = opendir($dirname);
        $files = array();
        while ($file = readdir($dir)) {
            if ($file != '.' && $file != '..') {
                $fullpath = "$dirname/$file";
                if (is_dir($fullpath)) {
                    $files = array_merge($files, $this->get_files_local_and_remote($fullpath, $basedir, $prefix));
                } else {
                    $files[$fullpath] = $prefix . ltrim(ltrim($fullpath, $basedir), '/');
                }
            }
        }
        closedir($dir);
        return $files;
    }
    /**
     * Upload all files in wp-content/uploads to QingStor Bucket.
     */
    public function upload_uploads()
    {
        if (($ret = qingstor_bucket_test()) != QS_REQUEST_OK) {
            return $ret;
        }
        $options = get_option('qingstor-options');
        $basedir = rtrim(wp_get_upload_dir()['basedir'], '/');
        $files   = $this->get_files_local_and_remote($basedir, $basedir, $options['upload_prefix']);
        $this->scheduled_upload_files($files);
        return QS_REQUEST_OK;
    }
    public function scheduled_upload_files($local_remote_path)
    {
        wp_schedule_single_event(time() + 1, 'qingstor_scheduled_upload_hook', array($local_remote_path));
    }
    /**
     * Upload multiple files.
     * @param array $local_remote_path Array(key: localpath, value: remote_path).
     */
    public function upload_files($local_remote_path)
    {
        define('MB', 1024 * 1024);
        define('GB', MB * 1024);
        define('TB', GB * 1024);
        set_time_limit(0);
        if (empty($bucket = qingstor_get_bucket())) {
            return QS_CLIENT_ERROR;
        }
        foreach ($local_remote_path as $local_path => $remote_path) {
            if (file_exists($local_path)) {
                /**
                 * If the file is less than 5GB, use putObject(), else use multipart upload.
                 * Not support the file larger than 50TB.
                 */
                if (($size = filesize($local_path)) < GB * 5) {
                    $res_upload_qingstor = $bucket->putObject($remote_path, array('body' => file_get_contents($local_path)));
                } elseif ($size < TB * 50) {
                    $offset = 0;
                    $step   = 5 * GB;
                    $nparts = 0;
                    // Use md5 value of the first 512 bytes of the file for etag.
                    $etag   = md5(file_get_contents($local_path, null, null, 0, 512));
                    $res = $bucket->initiateMultipartUpload($remote_path);
                    if (($ret = qingstor_http_status($res)) != QS_REQUEST_OK) {
                        return $ret;
                    }
                    $upload_id = $res->{'upload_id'};
                    // If there will be a block less than 4MB, then add 5GB to it and priority process.
                    if ($size % (5 * GB) < 4 * MB) {
                        $tmp_step = (int) ((($size % (5 * GB)) + 5 * GB) / 2 + 1);
                        for ($i = 0; $i < 2; $i++) {
                            $bucket->uploadMultipart(
                                $remote_path,
                                array(
                                    'upload_id' => $upload_id,
                                    'part_number' => $nparts,
                                    'body' => file_get_contents($local_path, null, null, $offset, $tmp_step)
                                )
                            );
                            $offset += $tmp_step;
                            $nparts++;
                        }
                    }
                    // The rest must be an integer multiple of 5GB.
                    while ($content = file_get_contents($local_path, null, null, $offset, $step)) {
                        $bucket->uploadMultipart(
                            $remote_path,
                            array(
                                'upload_id' => $upload_id,
                                'part_number' => $nparts,
                                'body' => $content
                            )
                        );
                        $offset += $step;
                        $nparts++;
                    }
                    // Complete multipart upload.
                    $res = $bucket->listMultipart(
                        $remote_path,
                        array(
                            'upload_id' => $upload_id
                        )
                    );
                    $object_parts = $res->{'object_parts'};
                    $res_upload_qingstor = $bucket->completeMultipartUpload(
                        $remote_path,
                        array(
                            'upload_id' => $upload_id,
                            'etag' => $etag,
                            'object_parts' => $object_parts
                        )
                    );
                }
                // delete the file after uploading successfully
                if ($res_upload_qingstor->statusCode == 201) {
                    wp_schedule_single_event(time() + 1, 'delete_local_file', array($local_path));
                }
            }
        }
    }
    /**
     * Upload files.
     * @param array $data array with the key 'file'
     */
    public function upload_data($data)
    {
        $wp_upload_dir = wp_get_upload_dir();
        $bucket = qingstor_get_bucket();
        $upload_prefix = get_option('qingstor-options')['upload_prefix'];
        if (empty($upload_prefix) || empty($bucket)) {
            return;
        }
        $files[$wp_upload_dir['basedir'] . '/' . $data['file']] = $upload_prefix . $data['file'];
        $this->upload_files($files);
    }
    /**
     * Hook function, delete the file after uploading to QingStor Bucket.
     * @param string $local_path The local path of the original file of the uploaded file.
     */
    public function delete_local_file($local_path)
    {
        if (file_exists($local_path)) {
            unlink($local_path);
        }
    }
    /**
     * Hook function, set url and baseurl of media file.
     * @param array $uploads Array includes path, basedir, subdir, url, baseurl.
     * @return $uploads Modified array.
     */
    public function upload_dir($uploads)
    {
        $options = get_option('qingstor-options');
        $uploads['url'] = $options['bucket_url'] . $options['upload_prefix'] . substr($uploads['subdir'], 1);
        $uploads['baseurl'] = $options['bucket_url'] . substr($options['upload_prefix'], 0, -1);
        return $uploads;
    }
    /**
     * Hook function, set to abandon genarate thumbnail.
     * @param array $sizes Array includes information about the file's size.
     * @return $sizes.
     */
    public function intermediate_image_sizes_advanced($sizes)
    {
        $sizes = null;
        return $sizes;
    }
}
QingStorUpload::get_instance();

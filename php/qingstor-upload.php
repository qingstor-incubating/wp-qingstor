<?php

define('QS_INVALID_BUCKET_URL', 31);

final class QingStorUpload
{
    private static $instance;

    public function __construct()
    {
        add_action('add_attachment', array($this, 'add_attachment'));
        add_filter('the_content', array($this, 'the_content'));
        add_filter('wp_calculate_image_srcset', array($this, 'calculate_image_srcset'));
        add_action('qingstor_scheduled_upload_hook', array($this, 'upload_files'));
    }

    public static function get_instance()
    {
        if (! (self::$instance instanceof QingStorUpload)) {
            self::$instance = new QingStorUpload();
        }
        return self::$instance;
    }

    public function get_files_local_and_remote($dirname, $basedir, $prefix) {
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

    // Upload wp-content/uploads/
    public function upload_uploads() {
        if (($ret = qingstor_bucket_test()) != QS_REQUEST_OK) {
            return $ret;
        }
        $options = get_option('qingstor-options');
        $basedir = rtrim(wp_get_upload_dir()['basedir'], '/');
        $files   = $this->get_files_local_and_remote($basedir, $basedir, $options['upload_prefix']);
        $this->scheduled_upload_files($files);
        return QS_REQUEST_OK;
    }

    public function scheduled_upload_files($local_remote_path) {
        wp_schedule_single_event(time() + 1, 'qingstor_scheduled_upload_hook', array($local_remote_path));
    }

    // Upload multiple files.
    public function upload_files($local_remote_path) {
        define('MB', 1024*1024);
        define('GB', MB*1024);
        define('TB', GB*1024);
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
                if (($size = filesize($local_path)) < GB*5) {
                    $bucket->putObject($remote_path, array('body' => file_get_contents($local_path)));
                } elseif ($size < TB*50) {
                    $offset = 0;
                    $step   = 5*GB;
                    $nparts = 0;
                    // Use md5 value of the first 512 bytes of the file for etag.
                    $etag   = md5(file_get_contents($local_path, null, null, 0, 512));

                    $res = $bucket->initiateMultipartUpload($remote_path);
                    if (($ret = qingstor_http_status($res)) != QS_REQUEST_OK) {
                        return $ret;
                    }
                    $upload_id = $res->{'upload_id'};
	                // If there will be a block less than 4MB, then add 5GB to it and priority process.
                    if ($size % (5*GB) < 4*MB) {
                        $tmp_step = (int)((($size % (5*GB)) + 5*GB) / 2 + 1);
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
                    $bucket->completeMultipartUpload(
                        $remote_path,
                        array(
                            'upload_id' => $upload_id,
                            'etag' => $etag,
                            'object_parts' => $object_parts
                        )
                    );
                }
            }
        }
    }

    /**
     * Upload files by metadata.
     * @param array $data  metadata or array with the key 'file'
     */
    public function upload_data($data)
    {
        $wp_upload_dir = wp_get_upload_dir();
        $bucket = qingstor_get_bucket();
        $upload_prefix = get_option('qingstor-options')['upload_prefix'];

        if (empty($upload_prefix) || empty($bucket)) {
            return;
        }

        // Upload Original image or other files.
        $files[$wp_upload_dir['basedir'] . '/' . $data['file']] = $upload_prefix . $data['file'];

        // Upload thumbnails.
        if (isset($data['sizes']) && count($data['sizes']) > 0) {
            foreach ($data['sizes'] as $key => $thumb_data) {
                $files[$wp_upload_dir['basedir'] . '/' . substr($data['file'], 0, 8) . $thumb_data['file']] =
                    $upload_prefix . substr($data['file'], 0, 8) . $thumb_data['file'];
            }
        }

        $this->scheduled_upload_files($files);
    }

    /**
     * Match the URL of Media files in article.
     * @param String $data
     * @return array
     */
    public function preg_match_all_url($data)
    {
        $options = get_option('qingstor-options');
        $p = '/=[\"|\'](https?:\/\/[^\s]*\.(' . $options['upload_types'] . '))[\"|\'| ]/iU';
        $num = preg_match_all($p, $data, $matches);

        return $num ? $matches[1] : array();
    }

    /**
     * Get the URL of Media files in QingStor Bucket.
     * @param $object
     * @return string
     */
    public function get_object_url($url)
    {
        $wp_upload_dir = wp_get_upload_dir();
        $options = get_option('qingstor-options');
        if (strstr($url, $wp_upload_dir['baseurl']) != false) {
            $object = end(explode($wp_upload_dir['baseurl'], $url));
            return $options['bucket_url'] . $options['upload_prefix'] . ltrim($object, '/');
        }
        return $url;
    }

    // Hook function. Auto Sync to QingStor Bucket when upload Media files to WordPress.
    public function add_attachment($post_ID)
    {
        $wp_upload_dir = wp_get_upload_dir();
        $attach_url = wp_get_attachment_url($post_ID);
        $file_path = $wp_upload_dir['basedir'] . '/' . ltrim($attach_url, $wp_upload_dir['baseurl']);
        $file_type = wp_check_filetype($file_path);

        if (strstr($file_type['type'], 'image') == false) {
            // 非图片文件
            $data = array('file' => end(explode($wp_upload_dir['basedir'] . '/', $file_path)));
        } else {
            $data = wp_generate_attachment_metadata($post_ID, $file_path);
        }
        $this->upload_data($data);
    }

    // Hook function. Replace the URL of Media files when article is rendering.
    public function the_content($content)
    {
        if (! get_option('qingstor-options')['replace_url']) {
            return $content;
        }
        $matches = $this->preg_match_all_url($content);

        foreach ($matches as $url) {
            $bucket_url = $this->get_object_url($url);
            $content = str_replace($url, $bucket_url, $content);
        }
        return $content;
    }

    // Hook function. Set srcset for images.
    public function calculate_image_srcset($src)
    {
        if (! get_option('qingstor-options')['replace_url']) {
            return $src;
        }
        $wp_upload_dir = wp_get_upload_dir();

        foreach ($src as $key => &$value) {
            if (strstr($value['url'], $wp_upload_dir['baseurl']) != false) {
                $bucket_url = $this->get_object_url($value['url']);
                $value['url'] = $bucket_url;
            }
        }
        return $src;
    }

    public function get_bucket_url_code($url) {
        $headers = get_headers($url);
        $code = substr($headers[0], 9, 3);
        if ($code != '200') {
            return QS_INVALID_BUCKET_URL;
        }
        return QS_REQUEST_OK;
    }
}

QingStorUpload::get_instance();

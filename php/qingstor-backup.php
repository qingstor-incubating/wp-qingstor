<?php

define('QS_ABSPATH_NOT_READABLE',    21);
define('QS_WP_CONTENT_NOT_WRITABLE', 22);
define('QS_NOZIP',                   23);
define('QS_NOMYSQLDUMP',             24);
define('QS_INVALID_RECURRENCE',      25);

final class QingStorBackup
{
    private static $instance;

    public function __construct()
    {
        add_action('qingstor_scheduled_backup_hook', array($this, 'backup'));
        add_action('qingstor_once_backup_hook', array($this, 'backup'));
        add_filter('cron_schedules', array($this, 'more_reccurences'));
    }

    public static function get_instance()
    {
        if (! (self::$instance instanceof QingStorBackup)) {
            self::$instance = new QingStorBackup();
        }
        return self::$instance;
    }

    public function scheduled_backup($recurrence) {
        $this->clear_schedule();
        if (($ret = $this->is_backup_possible()) != QS_REQUEST_OK) {
            return $ret;
        }
        if ($recurrence['schedule_type'] === 'manually') {
            return QS_REQUEST_OK;
        }
        if (! key_exists($recurrence['schedule_type'], wp_get_schedules())) {
            return QS_REQUEST_OK;
        }

        switch ($recurrence['schedule_type']) {
            case 'monthly':
                $time_str = $recurrence['start_day_of_month'] . ' ' . $recurrence['start_hours'] . ':' . $recurrence['start_minutes'];
                break;
            case 'weekly':
            case 'fortnightly':
                $time_str = $recurrence['start_day_of_week'] . ' ' . $recurrence['start_hours'] . ':' . $recurrence['start_minutes'];
                break;
            case 'daily':
            case 'twicedaily':
                $time_str = $recurrence['start_hours'] . ':' . $recurrence['start_minutes'];
                break;
            case 'hourly':
                $time_str = 'now';
                break;
            default:
                return QS_INVALID_RECURRENCE;
        }
        $timestamp = strtotime($time_str) - ($time_str === 'now' ? 0 : get_option('gmt_offset') * HOUR_IN_SECONDS);
        wp_schedule_event($timestamp, $recurrence['schedule_type'], 'qingstor_scheduled_backup_hook');
        return QS_REQUEST_OK;
    }

    public function clear_schedule() {
        wp_clear_scheduled_hook('qingstor_scheduled_bakcup_hook');
        wp_clear_scheduled_hook('qingstor_once_backup_hook');
    }

    // Trigger after one second for "Backup Now"
    public function once_bakcup() {
        if (($ret = $this->is_backup_possible()) != QS_REQUEST_OK) {
            return $ret;
        }
        wp_schedule_single_event(time() + 1, 'qingstor_once_backup_hook');
        return $ret;
    }

	/**
	 * Dump WordPress database, and zip wordpress directory to temporary directory,
	 * then upload zip file to QingStor Bucket and delete the temporary directory.
	 */
    public function backup()
    {
        set_time_limit(0);
        if (! $this->is_backup_possible()) {
            return;
        }
        $options = get_option('qingstor-options');
        $backup_path = $this->get_backup_path();

        // zip ABSPATH.
        $cwd = getcwd();
        chdir(ABSPATH);
        $command = 'zip -r ' . $backup_path['zip_path'] . ' .' ;
        exec($command, $output, $retvar1);

        // mysqldump the WordPress database.
        unset($output);
        $mysql_connect_args = $this->get_mysql_connect_args();
        $command = 'mysqldump ' . $mysql_connect_args . ' > ' . $backup_path['database_path'];
        exec($command, $output, $retvar2);
        // Add database.sql to the backup zip file.
        chdir($backup_path['backup_dir']);
        unset($output);
        $command = 'zip -m ' . $backup_path['zip_path'] . ' ' . wp_basename($backup_path['database_path']);
        exec($command, $output, $retvar3);
        chdir($cwd);

        // Make Sure that the three commands are successful (return 0) and upload to Bucket.
        if (! $retvar1 && ! $retvar2 && ! $retvar3) {
            $this->check_backups_num();

            $bucket_zip_path = $options['backup_prefix'] . wp_basename($backup_path['zip_path']);
            $files[$backup_path['zip_path']] = $bucket_zip_path;
            QingStorUpload::get_instance()->upload_files($files);
            // Mail notification.
            $this->send_mail($bucket_zip_path);
        }
        // Delete the temporary directory.
        if (file_exists($backup_path['backup_dir'])) {
            $this->deldir($backup_path['backup_dir']);
        }
    }

	// Check the number of backups in the Bucket. If lager than user's setting, delete earlier.
    public function check_backups_num() {
        if (empty($bucket = qingstor_get_bucket())) {
            return;
        }
        $options = get_option('qingstor-options');
        $res = $bucket->listObjects(array('prefix' => $options['backup_prefix']));
        if (qingstor_http_status($res) == QS_REQUEST_OK) {
            $files = $res->{'keys'};
            $backups = array();
            foreach ($files as $f) {
                if (strstr($f['mime_type'], 'application/zip') && preg_match('/wordpress-\d{4}-\d{2}-\d{2}-\d{2}-\d{2}\.zip/iU', $f['key'])) {
                    $backups[] = $f['key'];
                }
            }
            rsort($backups);
            while (count($backups) >= $options['backup_num']) {
                $bucket->deleteObject(end($backups));
                array_pop($backups);
            }
        } else {
            return QS_CLIENT_ERROR;
        }
    }

    // Hook function, add more recurences for scheduled backup.
    function more_reccurences() {
        return array(
            'weekly' => array('interval' => 604800, 'display' => 'Once Weekly'),
            'fortnightly' => array('interval' => 1209600, 'display' => 'Once Every Two Weeks'),
            'monthly' => Array ( 'interval' => 2592000, 'display' => 'Once Monthly')
        );
    }

	// Get username, password and database name for mysqldump.
    public function get_mysql_connect_args() {
        global $wpdb;

        $args = '';
        $args .= '-u ' . escapeshellarg($wpdb->dbuser);
        if ($wpdb->dbpassword) {
            $args .= ' -p' . escapeshellarg($wpdb->dbpassword);
        }
        $args .= ' ' . $wpdb->dbname;

        return $args;
    }

    // Delete a directory.
    public function deldir($dirname) {
        $dir = opendir($dirname);
        while ($file = readdir($dir)) {
            if ($file != '.' && $file != '..') {
                $fullpath = "$dirname/$file";
                if (is_dir($fullpath)) {
                    $this->deldir($fullpath);
                } else {
                    unlink($fullpath);
                }
            }
        }
        closedir($dir);
        rmdir($dirname);
    }

	// Generate a random string of a certain length for the name of temporary directory.
    public function generate_rand_string($length = 8) {
        static $chars = 'qwertyuiopasdfghjklzxcvbnm0123456789';
        $str = '';

        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }

	// Get the path of temporary directory.
    public function get_backup_path()
    {
        $options = get_option('qingstor-options');
        $backup_dir = $options['backup_dir'];
        if (! isset($backup_dir) || ! file_exists($backup_dir)) {
            $rand_suffix = $this->generate_rand_string();
            $backup_dir =  WP_CONTENT_DIR . "/QingStor-Backup-$rand_suffix";
            $options['backup_dir'] = $backup_dir;
            mkdir($backup_dir);
            chmod($backup_dir, 0777);
            update_option('qingstor-options', $options);
        }
        $zip_path = $backup_dir . '/wordpress-' . current_time('Y-m-d-H-i') . '.zip';
        $database_path = $backup_dir . '/database-' . current_time('Y-m-d-H-i') . '.sql';
        return array('zip_path' => $zip_path, 'database_path' => $database_path, 'backup_dir' => $backup_dir);
    }

    public function schedule_hook_run()
    {
        $this->backup();
    }

    public function zip_cmd_test()
    {
        exec('zip --version', $output, $return_var);
        if ($return_var != 0) {
            return false;
        }
        return true;
    }

    public function mysqldump_cmd_test()
    {
        exec('mysqldump --version', $output, $return_var);
        if ($return_var != 0) {
            return false;
        }
        return true;
    }

    // Check if /wordpress (ABSPATH) is readable and if zip and mysqldump is available.
    function is_backup_possible()
    {
        if (! wp_is_writable(WP_CONTENT_DIR) || ! is_readable(WP_CONTENT_DIR)) {
            return QS_WP_CONTENT_NOT_WRITABLE;
        }
        if (! is_readable(ABSPATH)) {
            return QS_ABSPATH_NOT_READABLE;
        }
        if (! $this->zip_cmd_test()) {
            return QS_NOZIP;
        }
        if (! $this->mysqldump_cmd_test()) {
            return QS_NOMYSQLDUMP;
        }
        return qingstor_bucket_test();
    }

    public function send_mail($filename) {
        if (! ($to = get_option('qingstor-options')['mailaddr'])) {
            return;
        }

        $message = 'QingStor backup ' . $filename . ' at ' . date('Y/m/d H:i', current_time('timestamp'));
        $headers = 'From: wordpress@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME'])) . "\n";
        @wp_mail($to, get_bloginfo('name') . ' ' . 'WordPress backup', $message, $headers);
    }
}

QingStorBackup::get_instance();

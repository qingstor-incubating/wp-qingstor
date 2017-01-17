<?php

add_action('admin_menu', 'qingstor_settings_menu');
function qingstor_settings_menu()
{
    add_options_page('QingStor', 'QingStor', 'manage_options', 'qingstor', 'qingstor_settings_page');
}

function qingstor_settings_page()
{
    $qingstor_error = QS_REQUEST_OK;
    $options = get_option('qingstor-options');
    if ($_REQUEST['once_backup']) {
        $qingstor_error = QingStorBackup::get_instance()->once_bakcup();
    } elseif ($_REQUEST['upload_uploads']) {
        // Upload wp-content/uploads/ directory.
        $qingstor_error = QingStorUpload::get_instance()->upload_uploads();
    } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (! empty($_POST['access_key'])) {
            $options['access_key'] = qingstor_test_key($_POST['access_key']);
        }
        if (! empty($_POST['secret_key'])) {
            $options['secret_key'] = qingstor_test_key($_POST['secret_key']);
        }
        if (! empty($_POST['bucket_name'])) {
            $options['bucket_name'] = qingstor_test_bucket_name($_POST['bucket_name']);
            // Set policy of the Bucket.
            update_option('qingstor-options', $options);
            if ($_POST['set_policy']) {
                $qingstor_error = qingstor_bucket_init();
            }
        }
        if (! empty($_POST['upload_types'])) {
            $options['upload_types'] = qingstor_test_input($_POST['upload_types']);
        }
        if (! empty($_POST['upload_prefix'])) {
            $options['upload_prefix'] = qingstor_test_prefix($_POST['upload_prefix']);
        }
        if (! empty($_POST['backup_prefix'])) {
            $options['backup_prefix'] = qingstor_test_prefix($_POST['backup_prefix']);
        }
        if (! empty($_POST['bucket_url'])) {
            $options['bucket_url'] = qingstor_test_url($_POST['bucket_url']);
        }
        if ($_POST['replace_url']) {
            $options['replace_url'] = true;
            $qingstor_error = QingStorUpload::get_instance()->get_bucket_url_code($options['bucket_url']);
        } else {
            $options['replace_url'] = false;
        }
        if ($_POST['set_policy']) {
            $options['set_policy'] = true;
        } else {
            $options['set_policy'] = false;
        }
        if (! empty($_POST['backup_num'])) {
            $options['backup_num'] = qingstor_test_num($_POST['backup_num'], 1, 1000);
        }
        if ($_POST['sendmail']) {
            $options['sendmail'] = true;
        } else {
            $options['sendmail'] = false;
        }
        if (! empty($_POST['mailaddr'])) {
            $options['mailaddr'] = qingstor_test_email($_POST['mailaddr']);
        }
        $options['schedule_recurrence'] = $_POST['schedule_recurrence'];
        update_option('qingstor-options', $options);
        $qingstor_error_save = QingStorBackup::get_instance()->scheduled_backup($_POST['schedule_recurrence']);
        if ($qingstor_error == QS_REQUEST_OK) {
            $qingstor_error = $qingstor_error_save;
        }
    }

    $qingstor_upload_types  = $options['upload_types'];
    $qingstor_upload_prefix = $options['upload_prefix'];
    $qingstor_backup_prefix = $options['backup_prefix'];
    $qingstor_mail          = $options['mailaddr'];
    $qingstor_nbackup       = $options['backup_num'];
    $qingstor_recurrence    = $options['schedule_recurrence'];
    $qingstor_access        = $options['access_key'];
    $qingstor_secret        = $options['secret_key'];
    $qingstor_bucket        = $options['bucket_name'];
    $qingstor_replace_url   = $options['replace_url'];
    $qingstor_bucket_url    = $options['bucket_url'];
    $qingstor_set_policy    = $options['set_policy'];

    require_once 'qingstor-setting-page.php';
}

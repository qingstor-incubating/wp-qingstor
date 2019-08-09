<?php
/*
Plugin Name: WP-QingStor
Plugin URI:  https://github.com/yunify/wp-qingstor
Description: QingStor Plugin for WordPress, support auto sync Media Library and delete local files. This project is based on the secondary development of yunify/wp-qingstor.
Text Domain: wp-qingstor
Domain Path: languages/
Version:     0.4.0
Author:      yungkcx, maydusa
*/

define('MINUMUM_PHP_VERSION', '5.6.0');
define('MINUMUM_WP_VERSION', '4.5');

if (version_compare(PHP_VERSION, MINUMUM_PHP_VERSION, '<'))
    wp_die(__('<p>The <strong>WP-QingStor</strong> plugin requires PHP version ' . MINUMUM_PHP_VERSION . ' or higher.</p>', 'wp-qingstor'), 'Plugin Activation Error', array('response' => 200, 'back_link' => TRUE));

require_once 'vendor/autoload.php';
require_once 'php/qingstor-functions.php';
require_once 'php/qingstor-upload.php';
require_once 'php/qingstor-menu.php';

register_activation_hook(__FILE__, 'qingstor_activation');
register_uninstall_hook(__FILE__, 'qingstor_uninstall');

// Add textdomain.
add_action('init', 'qingstor_load_textdomain');
function qingstor_load_textdomain()
{
    load_plugin_textdomain('wp-qingstor', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

function qingstor_activation()
{
    // Check if WP Cron is available.
    if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON == True) {
        deactivate_plugins(basename(__FILE__));
        wp_die(__('<p>WP Cron is disabled and is required by <strong>WP-QingStor</strong>. Please define DISABLE_WP_CRON as false in wp-config.php.</p>', 'wp-qingstor'), 'Plugin Activation Error', array('response' => 200, 'back_link' => TRUE));
        return;
    }

    // Check PHP and WordPress version.
    global $wp_version;
    $flag = '';
    if (version_compare(PHP_VERSION, MINUMUM_PHP_VERSION, '<')) {
        $flag = 'PHP';
    } elseif (version_compare($wp_version, MINUMUM_WP_VERSION, '<')) {
        $flag = 'WordPress';
    }
    if ($flag !== '') {
        $version = 'PHP' == $flag ? MINUMUM_PHP_VERSION : MINUMUM_WP_VERSION;
        deactivate_plugins(basename(__FILE__));
        wp_die(__('<p>The <strong>WP-QingStor</strong> plugin requires ' . $flag . ' version ' . $version . ' or higher.</p>', 'wp-qingstor'), 'Plugin Activation Error', array('response' => 200, 'back_link' => TRUE));
        return;
    }

    // Initialization options.
    $options = array(
        'upload_types'  => 'jpg|jpeg|png|gif|mp3|doc|pdf|ppt|pps',
        'upload_prefix' => 'files',
        'bucket_url'    => 'https://bucket-name.pek3a.qingstor.com/',
        'set_policy'    => true
    );
    update_option('qingstor-options', $options);
}

function qingstor_uninstall()
{
    QingStorBackup::get_instance()->clear_schedule();
    delete_option('qingstor-options');
}

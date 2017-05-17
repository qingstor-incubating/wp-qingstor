<?php

use QingStor\SDK\Service\QingStor;
use QingStor\SDK\Config;

define('QS_CLIENT_ERROR',  11);
define('QS_SERVER_ERROR',  12);
define('QS_REQUEST_OK',    13);
define('QS_MAX_STRLEN',  2048);
define('QS_MAX_KEYLEN',    40);

/**
 * Test the returned statusCode of QingStor SDK.
 * @param $response
 * @return int
 */
function qingstor_http_status($response)
{
    if ($response->statusCode >= 500) {
        return QS_SERVER_ERROR;
    } elseif ($response->statusCode >= 400) {
        return QS_CLIENT_ERROR;
    } else {
        return QS_REQUEST_OK;
    }
}

function qingstor_display_message($type, $error=NULL)
{
    switch ($type) {
    case 'errors':
        switch ($error) {
        case QS_CLIENT_ERROR:
            $message = __('Incorrect Bucket Settings.', 'wp-qingstor');
            break;
        case QS_SERVER_ERROR:
            $message = __('QingStor server error, please wait.', 'wp-qingstor');
            break;
        case QS_ABSPATH_NOT_READABLE:
            $message = __('WordPress directory is not readable.', 'wp-qingstor');
            break;
        case QS_WP_CONTENT_NOT_WRITABLE:
            $message = __('wp-content directory is not writable.', 'wp-qingstor');
            break;
        case QS_NOZIP:
            $message = __('Require zip.', 'wp-qingstor');
            break;
        case QS_NOMYSQLDUMP:
            $message = __('Require mysqldump.', 'wp-qingstor');
            break;
        case QS_INVALID_BUCKET_URL:
            $message = __('Invalid Bucket URL.', 'wp-qingstor');
            break;
        }
?>
    <div id="message" class="error">
        <p><?php echo $message; ?></p>
    </div>
<?php
        break;
    case 'once_backup':
    case 'upload_uploads':
    case 'settings':
?>
    <div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible">
        <p>
            <strong><?php 
            if ($type == 'settings') {
                _e('Settings saved.', 'wp-qingstor');
            } else {
                _e('Background task start.', 'wp-qingstor');
            }
            ?></strong>
        </p>
    </div>
<?php
        break;
    }
}

/**
 * Test access key and secret key，return service if OK, else return null.
 * @return null|QingStor
 */
function qingstor_get_service()
{
    $qingstor_options = get_option('qingstor-options');
    if (empty($qingstor_options)) {
        return NULL;
    }
    $config = new Config($qingstor_options['access_key'], $qingstor_options['secret_key']);
    $service = new QingStor($config);

    $res = $service->listBuckets();
    if (qingstor_http_status($res) != QS_REQUEST_OK) {
        return NULL;
    }
    return $service;
}

function qingstor_get_zone($bucket_name)
{
    if (empty($service = qingstor_get_service())) {
        return QS_CLIENT_ERROR;
    }
    $url = sprintf("%s://%s.%s:%s",$service->config->protocol, $bucket_name, $service->config->host, $service->config->port);
    stream_context_set_default(
        array(
            'http' => array(
                'method' => 'HEAD'
            )
        )
    );
    $headers = get_headers($url,1);
    if (isset($headers["Location"])){
        return explode(".",$headers["Location"])[1];
    }
    else{
        return NULL;
    }
}

function qingstor_bucket_test()
{
    if (empty($service = qingstor_get_service())) {
        return QS_CLIENT_ERROR;
    }
    $options = get_option('qingstor-options');
    $bucket = $service->Bucket($options['bucket_name'], qingstor_get_zone($options['bucket_name']));
    $res = $bucket->head();
    return qingstor_http_status($res);
}

/**
 * Test bucket_name on QingStor，reuturn bucket if the Bucket is exists, else return null.
 * @return null|\QingStor\SDK\Service\Bucket
 */
function qingstor_get_bucket()
{
    $service = qingstor_get_service();
    if (empty($service)) {
        return NULL;
    }
    $options = get_option('qingstor-options');
    $bucket = $service->Bucket($options['bucket_name'], qingstor_get_zone($options['bucket_name']));
    $res = $bucket->head();
    if (($ret = qingstor_http_status($res)) != QS_REQUEST_OK) {
        return NULL;
    }
    return $bucket;
}

// Set ACL and policy for the Bucket.
function qingstor_bucket_init()
{
    if (empty($bucket = qingstor_get_bucket())) {
        return QS_CLIENT_ERROR;
    }
    $options = get_option('qingstor-options');

    $response = $bucket->getACL();
    if (($ret = qingstor_http_status($response)) != QS_REQUEST_OK) {
        return $ret;
    }
    $userid = $response->owner['id'];

    $res = $bucket->putACL(
        array(
            'acl' => array(
                array(
                    'grantee'    => array(
                        'type'   => 'user',
                        'id'     => $userid
                    ),
                    'permission' => 'FULL_CONTROL'
                ),
                array(
                    'grantee'    => array(
                        'type'   => 'group',
                        'name'     => 'QS_ALL_USERS'
                    ),
                    'permission' => 'READ'
                )
            )
        )
    );
    if (($ret = qingstor_http_status($res)) != QS_REQUEST_OK) {
        return $ret;
    }

    $res = $bucket->getPolicy();
    $current_policy = $res->statement;
    foreach ($current_policy as $key => $value) {
        if (empty($value['resource'])) {
            $current_policy[$key]['resource'] = array($options['bucket_name']);
        }
    }
    $bucket->putPolicy(
        array(
            'statement' => array_merge($current_policy, array(
                array(
                    'id'       => 'backup-blacklist',
                    'user'     => '*',
                    'action'   => array('get_object', 'create_object', 'delete_object', 'head_object', 'list_object_parts', 'upload_object_part', 'abort_multipart_upload', 'initiate_multipart_upload', 'complete_multipart_upload'),
                    'effect'   => 'deny',
                    'resource' => array($options['bucket_name'] . '/' . $options['backup_prefix'] . '*'),
                )
            ))
        )
    );
    return QS_REQUEST_OK;
}

// Test input for <form>.
function qingstor_test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return strlen($data) > QS_MAX_STRLEN ? substr($data, 0, QS_MAX_STRLEN) : $data;
}

function qingstor_test_key($key)
{
    $key = qingstor_test_input($key);
    if (strlen($key) > QS_MAX_KEYLEN) {
        $key = '';
    }
    return $key;
}

function qingstor_test_bucket_name($name)
{
    $name = qingstor_test_input($name);
    if (strlen($name) > 63 || strlen($name) < 6) {
        $name = 'bucket-name';
    }
    return $name;
}

function qingstor_test_prefix($prefix) {
    $prefix = sanitize_text_field($prefix);
    return ltrim(rtrim($prefix, '/') . '/', '/');
}

function qingstor_test_url($url) {
    $url = esc_url($url);
    return rtrim($url, '/') . '/';
}

function qingstor_test_num($num, $min, $max)
{
    $num = intval($num);
    if (! $num || $num > $max || $num < $min) {
        return $min;
    }
    return $num;
}

function qingstor_test_email($email)
{
    $email = sanitize_email($email);
    if (is_email($email)) {
        return $email;
    }
    return '';
}

// Get URL of current page for redirect.
function qingstor_get_page_url()
{
    $pageURL = 'http';

    if ($_SERVER["HTTPS"] == "on")
    {
        $pageURL .= "s";
    }
    $pageURL .= "://";

    $this_page = $_SERVER["REQUEST_URI"];

    if (strpos($this_page, "?") !== false)
    {
        $this_pages = explode("?", $this_page);
        $this_page = reset($this_pages);
    }

    if ($_SERVER["SERVER_PORT"] != "80")
    {
        $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $this_page;
    }
    else
    {
        $pageURL .= $_SERVER["SERVER_NAME"] . $this_page;
    }
    return $pageURL;
}

// After `Upload the directory wp-content/uploads/' or `Backup Now'.
function qingstor_redirect()
{
    $url = qingstor_get_page_url() . '?page=qingstor';
    echo "<script language='javascript' type='text/javascript'>";
    echo "window.location.href='$url'";
    echo "</script>";
}

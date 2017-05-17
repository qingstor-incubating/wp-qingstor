=== WP-QingStor ===

Contributors:       yungkcx
Tags:               wordpress, Backup, QingStor
Requires at least:  4.5
Tested up to:       4.7
Stable tag:         trunk
License:            GPLv2 or later
License URI:        http://www.gnu.org/licenses/gpl-2.0.html

QingStor Plugin for WordPress, support scheduled backup and auto sync Media Library.

== Description == 

Please go to [QingCloud Console](https://console.qingcloud.com/access_keys/) to create `Access Key`, `Secret Key` and a Bucket for WordPress.

After setting:

1. Auto sync to QingStor Bucket when uploading Media files to WordPress Media Library.
2. After selecting `Automatically Replace the Media Files URL`, the plugin will auto replace the local URL of Media files with QingStor Bucket URL when the article is rendering.
3. Email notification of Scheduled Backup depends on PHP email settings.
4. The backup function requires `zip` and `mysqldump` command.
5. If the option `Automatically Set Policy` is checked, the plugin will set Acl as 'public read' and set Bucket Policy as 'deny all users to get object from backup prefix'. If not necessary, do not change it.

== Installation ==

1. Upload the plugin to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Settings->QingStor screen to configure the plugin.
4. Only support PHP 5.6 or higher.

== Screenshots ==

1. QingStor Scheduled Backup
2. QingStor Settings

== Changelog ==

= 0.3.4 =
* Fixed hardcode Bucket zone problem
* Fixed an error while saving configuration
* Fixed PHP version checking

= 0.3.3 =
* Added error detection when saving settings
* Fixed tab display error when switching
* Make a little adjustment to the interface

= 0.3.2 =
* Used tabs for sections

= 0.3.1 =
* Added some prompt message
* Added detection for PHP version and zip and mysqldump
* Added option for auto set the policy of Bucket

= 0.3 =
* Fixed the problem that the Media files could not be synchronized
* The Policy of the Bucket is no longer automatically set

= 0.2 =
* Initial Version

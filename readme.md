# wp-qingstor
QingStor Plugin for WordPress, support auto sync Media Library and delete local files.
This project is based on the secondary development of this project.

Modifications

- Remove backup module.
- Modify some configuration.
- Modify setting page.
- Add delete file on QingStor Bucket function.
- Update wp-qingstor-zh_CN, README and other related files.

Features

- Local file will be deleted after uploading to QingStor Bucket successfully.
- No longer generate thumbnails.
- The file address in the database is the address on the QingStor.

Installation

1. Upload the plugin to the 'wp-content/plugins' directory.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Setting->QingStor screen to configure the plugin.
4. Only support PHP 5.6 or higher.
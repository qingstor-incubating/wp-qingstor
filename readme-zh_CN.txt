=== QingStor 对象存储 ===

Contributors:       yungkcx, maydusa
Tags:               wordpress, QingStor
Requires at least:  4.5
Tested up to:       4.7
Stable tag:         trunk
License:            GPLv2 or later
License URI:        http://www.gnu.org/licenses/gpl-2.0.html

QingStor 对象存储服务 WordPress 插件，自动同步媒体库。

== Description ==

请首先前往 [QingCloud 控制台](https://console.qingcloud.com/access_keys/) 创建 `Access Key`，`Secret Key` 和一个用于 WrodPress 的 Bucket。

当你设置好插件的各项参数并启用后：

1. 向媒体库上传文件时，会自动上传到设置好的 QingStor Bucket，不会在本地留存图片和缩略图。
2. 开启 `自动设置存储空间策略` 后，插件会设置 Bucket 的权限为‘公开可读’，以及设置存储空间策略为‘禁止所有用户对备份文件所在目录操作’。如非必要，无需修改。

== Installation ==

1. 上传插件到 `/wp-content/plugins/` 目录。
2. 在后台插件菜单激活该插件。
3. 在 设置->QingStor 里设置好各项各项参数即可。
4. 仅支持 PHP5.6 或更高版本。

== Screenshots ==

1. QingStor 设置
2. 上传设置

== Changelog ==

= 0.4.0 =
* 上传文件不会在本地留存，对于图片，不会生成缩略图
* 移除备份模块

= 0.3.4 =
* 修复了 Bucket zone 的错误
* 修复了保存设置时的一个可能的错误
* 修复了 PHP 版本检查

= 0.3.3 =
* 添加了保存设置时的错误检测
* 修复了 tab 切换时的显示错误
* 对操作界面做了一点调整

= 0.3.2 =
* 使用 tab 区分各 section

= 0.3.1 =
* 添加了一部分提示消息
* 添加了 PHP 版本以及 zip 和 mysqldump 的检测
* 添加了自动设置存储空间策略的选项

= 0.3 =
* 修复了 Media 文件不能同步的问题
* 不再自动设置 Bucket 的存储空间策略

= 0.2 =
* 初始版本

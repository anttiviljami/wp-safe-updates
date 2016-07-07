=== WordPress Safe Updates ===
Contributors: Zuige
Tags: safe, tested, updates
Donate link: https://github.com/anttiviljami
Requires at least: 4.0
Tested up to: 4.5.3
Stable tag: 1.0.1
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

== Description ==

Test WordPress plugin updates safely before applying them on the live site.

**Contributing**

Please contribute to this project on Github. Pull requests welcome!

https://github.com/anttiviljami/wp-safe-updates

== Installation ==

**Configuration**

Paste these lines to your `wp-config.php`.

`/**
 * WordPress Safe Updates required configuration
 */
function currheap() {
  return isset( $_COOKIE['_alt_heap'] ) && ! empty( $_COOKIE['_alt_heap'] ) ? preg_replace('/[^a-z0-9_]/', '', strtolower( $_COOKIE['_alt_heap'] ) ) : false;
}
defined( 'WP_CONTENT_DIR' ) || define('WP_CONTENT_DIR', dirname(__FILE__) . '/wp-content');
defined( 'WP_CONTENT_URL' ) || define('WP_CONTENT_URL', '/wp-content');
if( false !== currheap() ) {
  defined( 'WP_PLUGIN_DIR' ) || define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins_tmp_' . currheap() );
  defined( 'WP_PLUGIN_URL' ) || define( 'WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins_tmp_' . currheap() );
  defined( 'PLUGINDIR' ) || define( 'PLUGINDIR', 'wp-content/plugins_tmp_' . currheap() );
}`

And copy the `db.php` file from this plugin to your wp-content directory.

== Frequently Asked Questions ==

None yet.

== Screenshots ==

1. The 'test update' button when an update is available for a plugin
2. Updating the plugin safely in an alternative heap
3. Testing the updated plugin

== Changelog ==

Commit log is available at https://github.com/anttiviljami/wp-safe-updates/commits/master

== Upgrade Notice ==

* 1.0 There's an update available to WordPress Safe Updates that makes it better. Please update it!

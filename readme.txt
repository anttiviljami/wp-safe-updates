=== WP Safe Updates ===
Contributors: Zuige
Tags: safe, tested, updates
Donate link: https://github.com/anttiviljami
Requires at least: 4.5
Tested up to: 4.6.1
Stable tag: 1.2.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

== Description ==

Test WordPress plugin updates safely before applying them on the live site.

This plugin adds a "test update" button when plugin updates are available. Clicking it will trigger the creation of a sandbox where you can safely test updating plugins without affecting the live site. Once finished testing the plugin, you can go back to the live site and do the real updates if all is well.

The sandbox works similarly to how WordPress multisite works. We basically tell WordPress to temporarily use a different database prefix and a different plugins directory while in the sandbox, which means while you test the update, no changes are made to the live site.

**Disclaimer**

Please make sure to always have backups of all your WordPress files and database before updating plugins or themes. We are not responsible for any misuse, deletions, white screens, fatal errors, or any other issue arising from using this plugin.

**Contributing**

Please contribute to this project on Github. Pull requests welcome!

https://github.com/anttiviljami/wp-safe-updates

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

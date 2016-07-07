# WP Safe Updates.
[![Latest Stable Version](https://poser.pugx.org/anttiviljami/wp-safe-updates/v/stable)](https://packagist.org/packages/anttiviljami/wp-safe-updates) [![Total Downloads](https://poser.pugx.org/anttiviljami/wp-safe-updates/downloads)](https://packagist.org/packages/anttiviljami/wp-safe-updates) [![Latest Unstable Version](https://poser.pugx.org/anttiviljami/wp-safe-updates/v/unstable)](https://packagist.org/packages/anttiviljami/wp-safe-updates) [![License](https://poser.pugx.org/anttiviljami/wp-safe-updates/license)](https://packagist.org/packages/anttiviljami/wp-safe-updates)

Test WordPress plugin updates safely before applying them on the live site.

Core trac ticket discussion: [#37301](https://core.trac.wordpress.org/ticket/37301)

## How does it work?

The purpose of this plugin is to enable the WordPress user to easily test plugin updates in a safe environment before actually updating in production.

We introduce a concept called *alternative heaps*, that work similarly to how WordPress Multisite works. When a certain cookie `_alt_heap` is defined, we tell WordPress temporarily to use a different set of database tables and different plugin directories.

Upon creating an alternative heap for testing, we clone all database tables from the live ones, and create a new plugins directory filled with symlinks to plugins. When updating a plugin, we replace that symlink with the up-to-date version of that plugin.

When updating a plugin, the user can choose to create an alternative heap where they can easily test the plugin before updating it on the live site.

## Screenshots

### The 'test update' button when an update is available for a plugin
![Test update link](/assets/screenshot-1.png)

### Updating the plugin safely in an alternative heap
![Updating a plugin](/assets/screenshot-2.png)

### Testing the updated plugin
![Testing the update](/assets/screenshot-3.png)

## Installation

### The Composer Way (preferred)

Install the plugin via [Composer](https://getcomposer.org/)
```
composer require anttiviljami/wp-safe-updates
```

Activate the plugin
```
wp plugin activate wp-safe-updates
```

### The Old Fashioned Way

You can also install the plugin by directly uploading the zip file as instructed below:

1. [Download the plugin](archive/master.zip)
2. Upload to the plugin to /wp-content/plugins/ via the WordPress plugin uploader or your preferred method
3. Activate the plugin

## Configuration

Paste these lines to your `wp-config.php`.
```php
/**
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
}
```

And copy the `db.php` file from this plugin to your wp-content directory.

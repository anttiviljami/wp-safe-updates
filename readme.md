# WP Safe Updates.
[![Latest Stable Version](https://poser.pugx.org/anttiviljami/wp-safe-updates/v/stable)](https://packagist.org/packages/anttiviljami/wp-safe-updates) [![Total Downloads](https://poser.pugx.org/anttiviljami/wp-safe-updates/downloads)](https://packagist.org/packages/anttiviljami/wp-safe-updates) [![Latest Unstable Version](https://poser.pugx.org/anttiviljami/wp-safe-updates/v/unstable)](https://packagist.org/packages/anttiviljami/wp-safe-updates) [![License](https://poser.pugx.org/anttiviljami/wp-safe-updates/license)](https://packagist.org/packages/anttiviljami/wp-safe-updates)

Test WordPress plugin updates safely before applying them on the live site.

Core trac ticket discussion: [#37301](https://core.trac.wordpress.org/ticket/37301)

## Disclaimer

**Please make sure to always have backups of all your WordPress files and database before updating plugins or themes. We are not responsible for any misuse, deletions, white screens, fatal errors, or any other issue arising from using this plugin.**

## How does it work?

This plugin adds a "test update" button when plugin updates are available. Clicking it triggers the creation of a sandbox where the user can safely test updating plugins without affecting the live site. Once the user is finished testing the plugin, they can go back to the live site and do updates if they like.

The sandbox works similarly to how WordPress multisite works. We tell WordPress to temporarily use a different database prefix and a different plugins directory while in the alternative heap (sandbox). This is done by sending WordPress a special _alt_heap cookie.

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

This plugin is available on the [official WordPress.org plugin directory](https://wordpress.org/plugins/wp-safe-updates/).

You can also install the plugin by directly uploading the zip file as instructed below:

1. [Download the plugin](archive/master.zip)
2. Upload to the plugin to /wp-content/plugins/ via the WordPress plugin uploader or your preferred method
3. Activate the plugin

## Configuration

First copy the `db.php` file from this plugin to your `wp-content` directory.

Then just paste these lines to your `wp-config.php`.
```php
/**
 * WordPress Safe Updates required configuration
 */
function currheap() {
  return isset( $_COOKIE['_alt_heap'] ) && ! empty( $_COOKIE['_alt_heap'] ) ? preg_replace('/[^a-z0-9_]/', '', strtolower( $_COOKIE['_alt_heap'] ) ) : false;
}
defined( 'WP_CONTENT_DIR' ) || define( 'WP_CONTENT_DIR', dirname( __FILE__ ) . '/wp-content' );
defined( 'WP_CONTENT_URL' ) || define( 'WP_CONTENT_URL', '/wp-content' );
if ( false !== currheap() ) {
  defined( 'WP_PLUGIN_DIR' ) || define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins_tmp_' . currheap() );
  defined( 'WP_PLUGIN_URL' ) || define( 'WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins_tmp_' . currheap() );
  defined( 'PLUGINDIR' ) || define( 'PLUGINDIR', 'wp-content/plugins_tmp_' . currheap() );
}
```


# WordPress Safe Updates.
[![Latest Stable Version](https://poser.pugx.org/anttiviljami/wp-safe-updates/v/stable)](https://packagist.org/packages/anttiviljami/wp-safe-updates) [![Total Downloads](https://poser.pugx.org/anttiviljami/wp-safe-updates/downloads)](https://packagist.org/packages/anttiviljami/wp-safe-updates) [![Latest Unstable Version](https://poser.pugx.org/anttiviljami/wp-safe-updates/v/unstable)](https://packagist.org/packages/anttiviljami/wp-safe-updates) [![License](https://poser.pugx.org/anttiviljami/wp-safe-updates/license)](https://packagist.org/packages/anttiviljami/wp-safe-updates)

Tested updates for WordPress plugins

## Screenshots

Screenshots here

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
  return isset( $_COOKIE['_alt_heap'] ) && ! empty( $_COOKIE['_alt_heap'] ) ? preg_replace('/[^a-z0-9]/', '', strtolower( $_COOKIE['_alt_heap'] ) ) : false;
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

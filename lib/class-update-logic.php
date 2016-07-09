<?php

class Update_Logic {
  public static $instance;

  public static function init() {
    if ( is_null( self::$instance ) ) {
      self::$instance = new Update_logic();
    }
    return self::$instance;
  }

  private function __construct() {
    if( function_exists( 'currheap' ) && false !== currheap() ) {
      // clear the symlink before updating a plugin so we don't accidentally do
      // anything with the live plugin
      add_filter( 'upgrader_pre_install', array( $this, 'unlink_old_plugin' ), 20, 2 );

      // disable maintenance mode
      add_filter( 'upgrader_package_options', array( $this, 'hack_disable_maintenance_mode' ) );
    }
    else {
      // hack the update notification string in update.php
      add_filter( 'gettext', array( $this, 'hack_plugin_update_text'), 10, 3 );
    }
  }

  /**
   * HACK: filter the gettext value of the update string for a plugin
   */
  public function hack_plugin_update_text( $translated_text, $untranslated_text, $domain ) {
    if( $untranslated_text === 'There is a new version of %1$s available. <a href="%2$s" class="thickbox open-plugin-details-modal" aria-label="%3$s">View version %4$s details</a> or <a href="%5$s" class="update-link" aria-label="%6$s">update now</a>.' ) {
      return __('There is a new version of %1$s available. <a href="%2$s" class="thickbox open-plugin-details-modal" aria-label="%3$s">View version %4$s details</a>, <a href="%5$s&alt_heap=update">test update</a> or <a href="%5$s" class="update-link" aria-label="%6$s">update now</a>.');
    }
    return $translated_text;
  }

  /**
   * HACK: Disable maintenance mode right after enabling it in alt heaps
   */
  public function hack_disable_maintenance_mode( $options ) {
    global $wp_filesystem;

    // delete the .maintenance file if it exists
    $file = $wp_filesystem->abspath() . '.maintenance';
    $wp_filesystem->delete( $file );

    // $options should be unchanged
    return $options;
  }

  /**
   * Before attempting an upgrade in an alt heap, remove the symlink
   */
  public function unlink_old_plugin( $removed, $args ) {
    global $wp_filesystem;

    $plugin = isset($args['plugin']) ? $args['plugin'] : '';
    if ( empty($plugin) )
      return new WP_Error('Invalid plugin.');

		$plugins_dir = $wp_filesystem->wp_plugins_dir();
    $this_plugin_dir = dirname( $plugins_dir . $plugin );

    // check if plugin is in it's own directory or if it's a file
    if ( strpos( $plugin, '/' ) && $this_plugin_dir != $plugins_dir ) // base check on if plugin includes directory separator AND that it's not the root plugin folder
      $to_delete = $this_plugin_dir;
    else
      $to_delete = $plugins_dir . $plugin;

    if( ! is_link( $to_delete ) ) {
      // it's not a link, just leave it to WP_Upgrader to do the normal upgrade
      return false;
    }

		// finally unlink
		unlink( $to_delete );
    return true;
  }
}


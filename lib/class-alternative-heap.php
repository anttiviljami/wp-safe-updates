<?php

/**
 * Modifies the current heap to fork some functionalities like which database
 * prefix is in use. This is used to create safe, non-public temporary
 * environments to test updates.
 */
class Alternative_Heap {
  public static $instance;

  public static function init() {
    if ( is_null( self::$instance ) ) {
      self::$instance = new Alternative_Heap();
    }
    return self::$instance;
  }

  public function __construct() {
    // display a notice at the bottom of the window when in an alternative heap
    if( function_exists( 'currheap' ) && false !== currheap() ) {
      add_action('admin_footer', array( $this, 'render_alternative_heap_indicator' ) );
      add_action('wp_footer', array( $this, 'render_alternative_heap_indicator' ) );
    }
  }

  /**
   * Returns the table prefix for an alternative heap
   */
  public static function get_alt_prefix( $alt_heap = "" ) {
    global $wpdb;
    $alt_prefix = $wpdb->prefix . 'tmp_';
    if( ! empty( $alt_heap ) ) {
      $alt_prefix .= $alt_heap . '_';
    }
    return $alt_prefix;
  }

  /**
   * Returns the plugins dir suffix for an alternative heap
   */
  public static function get_alt_suffix( $alt_heap = "" ) {
    if( empty( $alt_heap ) ) {
      return '';
    }
    return '_tmp_' . $alt_heap; // plugins_tmp_{alt_heap}
  }

  /**
   * Gets all the live WordPress prefixed tables (not our alternative ones)
   *
   * @return: An array of table names
   */
  public static function get_wp_tables() {
    global $wpdb;
    $tables = $wpdb->get_results( $wpdb->prepare( "SHOW TABLES LIKE %s;", str_replace( '_', '\_', $wpdb->prefix) . '%' ), ARRAY_N );
    $tables = array_map( 'reset', $tables );

    // no alternative tables
    $tables = array_diff($tables, self::get_tmp_wp_tables());
    return $tables;
  }

  /**
   * Gets all our alternative WordPress tables
   *
   * @return: An array of table names
   */
  public static function get_tmp_wp_tables( $alt_heap = "" ) {
    global $wpdb;
    $tables = $wpdb->get_results( $wpdb->prepare( "SHOW TABLES LIKE %s;", str_replace( '_', '\_', self::get_alt_prefix( $alt_heap ) ) . '%' ), ARRAY_N );
    $tables = array_map( 'reset', $tables );
    return $tables;
  }

  /**
   * Clones all WordPress original tables to an alternative heap namespace
   */
  public function clone_wp_tables( $alt_heap ) {
    global $wpdb;

    $old_prefix = $wpdb->prefix;
    $alt_prefix = self::get_alt_prefix( $alt_heap );

    $tables = self::get_wp_tables();
    foreach ( $tables as $table ) {
      $new_table = str_ireplace( $old_prefix, $alt_prefix, $table );

      set_time_limit(600); // allow up to 10 minutes for large db queries to finish
      $query = wp_sprintf( "DROP TABLE IF EXISTS %s;", $new_table );
      $wpdb->query( $query );

      set_time_limit(600);
      $query = wp_sprintf( "CREATE TABLE %s LIKE %s;", $new_table, $table );
      $wpdb->query( $query );

      set_time_limit(600);
      $query = wp_sprintf( "INSERT %s SELECT * FROM %s;", $new_table, $table );
      $wpdb->query( $query );

      // $wpdb->prefix is oddly used in other places too in the wp_options and
      // usermeta tables. We need to search-replace those.
      if( false !== strpos( $table, 'options' ) ) {
        set_time_limit(600);
        $query = wp_sprintf("UPDATE %s SET option_name='%suser_roles' WHERE option_name='%suser_roles';", $new_table, $alt_prefix, $old_prefix);
        $wpdb->query( $query );
      }
      if( false !== strpos( $table, 'usermeta' ) ) {
        $query = $wpdb->prepare("SELECT user_id, meta_key FROM $new_table WHERE meta_key LIKE %s;", str_replace( '_', '\_', $old_prefix ) . '%');
        $meta_keys = $wpdb->get_results( $query );
        foreach ( $meta_keys as $row ) {
          $old_key = $row->meta_key;
          $new_key = str_replace( $old_prefix, $alt_prefix, $old_key );
          $query = $wpdb->prepare("UPDATE $new_table SET meta_key=%s WHERE meta_key=%s;", $new_key, $old_key);
          $wpdb->query( $query );
        }
      }
    }
  }

  /**
   * Delete temp tables from an alternative heap namespace
   */
  public function delete_tmp_wp_tables( $alt_heap = "" ) {
    global $wpdb;

    $tables = self::get_tmp_wp_tables( $alt_heap );

    // extra step: make sure no original wp tables are included in the $tables array
    // there is no logical way for this to ever happen, but i feel better putting this here
    $tables = array_diff( $tables, self::get_wp_tables() );

    // still, please back up databases, ok? :)

    foreach ( $tables as $table ) {
      set_time_limit(600); // allow up to 10 minutes for large db queries to finish
      $query = wp_sprintf( "DROP TABLE IF EXISTS %s;", $table );
      $wpdb->query( $query );
    }
  }

  /**
   * Create a new plugins directory with symlinks to live plugins
   */
  public function create_alt_plugins_dir( $alt_heap = "" ) {
    $orig_plugins_dir = WP_PLUGIN_DIR;
    $alt_plugins_dir = WP_PLUGIN_DIR . self::get_alt_suffix( $alt_heap );

    // panic and exit early if directories are the same
    if( $orig_plugins_dir == $alt_plugins_dir ) {
      return false;
    }

    // create the new plugins directory if it doesn't exist. Otherwise, empty it.
    if ( ! file_exists( $alt_plugins_dir ) ) {
      mkdir( $alt_plugins_dir );
    }
    else {
      // OMG... RecursiveIteratorIterator. Standard PHP Lib, I love (hate) you <3
      // We use the CHILD_FIRST flag here in order to delete directories inside the plugins dir first
      $iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $alt_plugins_dir ), RecursiveIteratorIterator::CHILD_FIRST );
      foreach ( $iterator as $node ) {
				if ( in_array( $node->getBasename(), array('.', '..') ) ) {
          continue;
        } elseif ( $node->isFile() || $node->isLink() ) {
          unlink( $node->getPathname() );
        } else {
          rmdir( $node->getPathname() );
        }
      }
    }

    // we should now have an empty dir at $alt_plugins_dir
    // let's symlink all plugins from the orig dir
    $iterator = new DirectoryIterator( $orig_plugins_dir );
    foreach( $iterator as $node ) {
			$basename = $node->getBasename();
			if ( '.' === $basename[0] ) {
				continue;
			}

			// create symlink
      $link_target = $alt_plugins_dir . DIRECTORY_SEPARATOR . $basename;
      symlink( $node->getPath(), $link_target );
    }
    return $alt_plugins_dir;
  }

	/**
   * Deletes the temp plugins dir for an alternative heap
   */
  public function delete_alt_plugins_dir( $alt_heap = "" ) {
    $orig_plugins_dir = WP_PLUGIN_DIR;
    $alt_plugins_dir = WP_PLUGIN_DIR . self::get_alt_suffix( $alt_heap );

    // panic and exit early if directories are the same
    if( $orig_plugins_dir == $alt_plugins_dir ) {
      return false;
    }

		// recursively delete the alt plugins dir
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $alt_plugins_dir ), RecursiveIteratorIterator::CHILD_FIRST );
		foreach ( $iterator as $node ) {
			if ( in_array( $node->getBasename(), array('.', '..') ) ) {
				continue;
			} elseif ( $node->isFile() || $node->isLink() ) {
				unlink( $node->getPathname() );
			} else {
				rmdir( $node->getPathname() );
			}
		}
		rmdir( $alt_plugins_dir );
    return true;
	}

  /**
   * Display a notice at the bottom of the window when in an alternative heap
   */
  public function render_alternative_heap_indicator() {
?>
<style>#alt-heap-indicator { font-family: Arial, sans-serif; position: fixed; bottom: 0; left: 0; right: 0; width: 100%; color: #fff; background: #770000; z-index: 3000; font-size:18px; line-height: 1; text-align: center; padding: 5px }</style>
<div id="alt-heap-indicator">
<?php _e('You are currently in an alternative heap.', 'wp-safe-updates'); ?> (ID: <?php echo currheap(); ?>)
</div>
<?php
  }

}


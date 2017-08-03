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
    add_action('init', array( $this, 'maybe_create_alt_heap' ) );

    // display a notice at the bottom of the window when in an alternative heap
    if( function_exists( 'currheap' ) && false !== currheap() ) {
      add_filter('plugins_url', array( $this, 'fix_plugins_url' ) );
      add_action('admin_footer', array( $this, 'render_alternative_heap_indicator' ) );
      add_action('wp_footer', array( $this, 'render_alternative_heap_indicator' ) );
      add_action('login_footer', array( $this, 'render_alternative_heap_indicator' ) );
    }
  }

  /**
   * plugins_url() fix
   *
   * When symlinked, the plugins still think they are being run in their
   * realpath. This may cause issues for plugins_url since plugin_basename will
   * not work correctly. Since we can't hook directly there, we'll do it in
   * here.
   */
  public function fix_plugins_url( $url, $path = "", $plugin = "") {
    $plugins_dir = WP_PLUGIN_DIR;
    $default_plugins_dir = preg_replace( '#' . preg_quote( basename( $plugins_dir ) ) . '#', 'plugins', $plugins_dir );
    $url = preg_replace( '#' . preg_quote( $plugins_dir ) . '#', '', $url );
    $url = preg_replace( '#' . preg_quote( $default_plugins_dir ) . '#', '', $url );
    return $url;
  }

  /**
   * if $_GET[alt_heap] is defined and (@TODO: user has permissions), create that heap and return to it
   */
  public function maybe_create_alt_heap() {
    if( ! isset( $_GET['alt_heap'] ) ) {
      return; // nothing to do;
    }

    $query_vars = $_GET;
    $alt_heap = $query_vars['alt_heap'];

    if ( $alt_heap === 'clean' ) {
      // this means we want to clean the alt heap temp files and tables
      $this->delete_alt_plugins_dirs( $_COOKIE['_alt_heap'] );
      $this->delete_tmp_wp_tables( $_COOKIE['_alt_heap'] );

      // and then clear the cookie
      $alt_heap = '';
    }

    if( $alt_heap === 'clear' || $alt_heap === 'exit' ) {
      // this means we want to clear the heap cookie
      // equivalent to empty ?alt_heap=
      $alt_heap = '';
    }


    // not in a heap yet and GET alt_heap is defined and current user has plugin install privileges
    if( ! empty( $alt_heap ) && current_user_can( 'install_plugins' ) && ! currheap() ) {
      // create plugins dir for alt_heap
      $this->create_alt_plugins_dir( $alt_heap );

      // clone tables for alt_heap
      $this->clone_wp_tables( $alt_heap );

      // set the _alt_heap cookie
      setcookie('_alt_heap', $alt_heap, 0, '/');
    }
    else {
      // clear the alt heap cookie
      setcookie('_alt_heap', '', 0, '/');
    }

    // no need for alt_heap in query string anymore
    unset( $query_vars['alt_heap'] );

    // rebuild query string
    $query_string = http_build_query( $query_vars );
    $request_uri = substr( $_SERVER['REQUEST_URI'], 0, strpos( $_SERVER['REQUEST_URI'], '?' ) );
    $request_uri .= empty( $query_string ) ? '' : '?' . $query_string;

    // flush caches for this just in case
    wp_cache_flush();

    // redirect to requested page, but with alt heap this time
    wp_redirect( $request_uri );
    exit;
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
   * Derives the original db prefix from an alt heap prefix
   */
  public static function derive_orig_prefix( $alt_prefix ) {
    $offset = strpos( $alt_prefix, 'tmp_' ) ? strpos( $alt_prefix, 'tmp_' ) : strlen( $alt_prefix );
    $orig_prefix = substr( $alt_prefix, 0, $offset );

    // fall back to wp_ if all else fails
    if( empty( $orig_prefix ) ) {
      return 'wp_';
    }
    return $orig_prefix;
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
    $orig_prefix = isset( $_COOKIE['_alt_heap'] ) ? self::derive_orig_prefix( $wpdb->prefix ) : $wpdb->prefix;
    $tables = $wpdb->get_results( $wpdb->prepare( "SHOW TABLES LIKE %s;", str_replace( '_', '\_', $orig_prefix ) . '%' ), ARRAY_N );
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
    // alt prefix will already be $wpdb->prefix if currently in a heap
    $alt_prefix = isset( $_COOKIE['_alt_heap'] ) ? $wpdb->prefix : self::get_alt_prefix( $alt_heap );
    $tables = $wpdb->get_results( $wpdb->prepare( "SHOW TABLES LIKE %s;", str_replace( '_', '\_', $alt_prefix ) . '%' ), ARRAY_N );
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
      symlink( $node->getRealPath(), $link_target );
    }
    return $alt_plugins_dir;
  }

  /**
   * Returns existing alt plugins dir(s) as an array. If $alt_heap is defined, we return only that alt dir
   */
  public static function get_alt_plugins_dirs( $alt_heap = "" ) {
    $dirs = array();
    if( ! empty( $alt_heap ) ) {
      $suffix = self::get_alt_suffix( $alt_heap );

      // WP_PLUGIN_DIR will be the alt heap dir if in an alt heap
      if ( strpos( WP_PLUGIN_DIR, $suffix ) ) {
        $dirs[] = WP_PLUGIN_DIR;
      } else {
        $dirs[] = WP_PLUGIN_DIR . self::get_alt_suffix( $alt_heap );
      }

      // return false if the alt heap dir doesn't exist
      if( ! file_exists( $dirs[0] ) ) {
        return false;
      }
    }
    else {
      // otherwise just return all alt plugin dirs
      $iterator = new DirectoryIterator( dirname( WP_PLUGIN_DIR ) );
      foreach( $iterator as $node ) {
        $basename = $node->getBasename();
        if( 0 === strpos( $basename, basename( WP_PLUGIN_DIR ) . '_tmp_' ) ) {
          $dirs[] = $node->getPathname();
        }
      }
    }

    return $dirs;
  }

  /**
   * Deletes the temp plugins dir for an alternative heap
   */
  public function delete_alt_plugins_dirs( $alt_heap = "" ) {
    $suffix = self::get_alt_suffix( $alt_heap );
    $orig_plugins_dir = str_replace( $suffix, '', WP_PLUGIN_DIR );
    $alt_dirs_to_delete = self::get_alt_plugins_dirs( $alt_heap );

    foreach( $alt_dirs_to_delete as $alt_plugins_dir ) {
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
    }

    return true;
  }

  /**
   * Display a notice at the bottom of the window when in an alternative heap
   */
  public function render_alternative_heap_indicator() {
    // filter whether to show the alt_heap_indicator when inside an alternative heap
    $show_indicator = apply_filters( 'show_alt_heap_indicator', true );
    if( $show_indicator ) :
?>
<style>#alt-heap-indicator { font-family: Arial, sans-serif; position: fixed; bottom: 0; left: 0; right: 0; width: 100%; color: #fff; background: #770000; z-index: 10000; font-size:18px; line-height: 1; text-align: center; padding: 5px } #alt-heap-indicator a { color: #fff !important; text-decoration: underline; }</style>
<div id="alt-heap-indicator">
<?php echo wp_sprintf( __('You are currently testing updates. Any changes you make will not be saved.', 'wp-safe-updates'), currheap() ); ?>
 <a href="<?php echo admin_url('plugins.php?alt_heap=clean'); ?>"><?php _e('Finish tests', 'wp-safe-updates'); ?></a>
</div>
<?php
    endif;
  }
}


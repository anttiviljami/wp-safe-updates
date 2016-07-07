<?php
/**
 * Plugin name: WordPress Safe Updates
 * Plugin URI: https://github.com/anttiviljami/wp-safe-updates
 * Description: Tested updates for WordPress plugins
 * Version: 1.0
 * Author: @anttiviljami
 * Author URI: https://github.com/anttiviljami
 * License: GPLv3
 * Text Domain: wp-safe-updates
 */

/** Copyright 2016 Antti Kuosmanen

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 3, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! class_exists('Safe_Updates') ) :

class Safe_Updates {
  public static $instance;

  public $alt_heap;
  public $update_logic;

  public static function init() {
    if ( is_null( self::$instance ) ) {
      self::$instance = new Safe_Updates();
    }
    return self::$instance;
  }

  private function __construct() {
    global $wpdb;

    // load textdomain for translations
    add_action( 'plugins_loaded',  array( $this, 'load_our_textdomain' ) );

    if( function_exists( 'currheap' ) && is_a( $wpdb, 'safe_wpdb' ) ) {
      // plugin is already configured
      require_once 'lib/class-alternative-heap.php';
      require_once 'lib/class-update-logic.php';

      $this->alt_heap = Alternative_Heap::init();
      $this->update_logic = Update_Logic::init();

      if( isset( $_GET['tests'] ) ) {
        $this->run_tests();
      }
    }
    else {
      // show a notice to prompt the user to configure WP Safe Updates
      // @TODO: offer to do this automatically
      add_action( 'admin_notices', array( $this, 'not_configured_notice' ) );
    }

    // clear all heaps on uninstall
    register_uninstall_hook( __FILE__, array( 'Safe_Updates', 'uninstall_cleanup' ) );
  }

  /**
   * shows a notice to prompt the user to configure WP Safe Updates
   */
  public function not_configured_notice() {
?>
<div class="notice notice-warning is-dismissible">
  <?php $configure_action = 'https://github.com/anttiviljami/wp-safe-updates#configuration'; ?>
  <p><?php echo wp_sprintf( __('WP Safe Updates is not yet active. Please <a href="%s" target="_blank">configure</a> it.', 'wp-safe-updates'), $configure_action ); ?> <button type="button" class="notice-dismiss"></button></p>
</div>
<?php
  }

  /**
   * Unit tests (kind of)
   */
  private function run_tests() {
    echo "<pre>";

    // Unit tests (sort of)  -->
    echo "Original WordPress tables: \n";
    print_r( $this->alt_heap->get_wp_tables() );

    echo "Cloning to 'test'... \n";
    $this->alt_heap->clone_wp_tables('test');

    echo "Cloning to 'test2'... \n";
    $this->alt_heap->clone_wp_tables('test2');

    echo "All tmp tables: \n";
    print_r( $this->alt_heap->get_tmp_wp_tables() );

    echo "All 'test' tables: \n";
    print_r( $this->alt_heap->get_tmp_wp_tables('test') );

    echo "Creating plugins directory for 'test'... \n";
    $this->alt_heap->create_alt_plugins_dir('test');

    echo "Creating plugins directory for 'test2'... \n";
    $this->alt_heap->create_alt_plugins_dir('test2');

    echo "Alt plugin dirs: \n";
    print_r( $this->alt_heap->get_alt_plugins_dirs() );

    echo "'Test' plugin dir: \n";
    print_r( $this->alt_heap->get_alt_plugins_dirs('test') );

    echo "Deleting 'test' tables... \n";
    $this->alt_heap->delete_tmp_wp_tables('test');

    echo "Deleting 'test' plugins directory... \n";
    $this->alt_heap->delete_alt_plugins_dirs('test');

    echo "Alt plugin dirs: \n";
    print_r( $this->alt_heap->get_alt_plugins_dirs() );

    echo "Deleting all tmp plugins directories... \n";
    $this->alt_heap->delete_alt_plugins_dirs();

    echo "Alt plugin dirs: \n";
    print_r( $this->alt_heap->get_alt_plugins_dirs() );

    echo "All tmp tables: \n";
    print_r( $this->alt_heap->get_tmp_wp_tables() );

    echo "Deleting all tmp tables... \n";
    $this->alt_heap->delete_tmp_wp_tables();

    echo "All tmp tables: \n";
    print_r( $this->alt_heap->get_tmp_wp_tables() );

    echo "Original WordPress tables: \n";
    print_r( $this->alt_heap->get_wp_tables() );

    // <-- Unit tests done
    echo "</pre>";
    wp_die();
  }

  /**
   * Delete all alternative heap directories and tables on uninstall
   */
  public static function uninstall_cleanup() {
    require_once 'lib/class-alternative-heap.php';
    $alt_heap = Alternative_Heap::init();

    // Deleting all tmp plugins directories...
    $alt_heap->delete_alt_plugins_dirs();

    // Deleting all tmp tables...
    $alt_heap->delete_tmp_wp_tables();
  }

  /**
   * Load our textdomain
   */
  public static function load_our_textdomain() {
    load_plugin_textdomain( 'wp-safe-updates', false, dirname( plugin_basename(__FILE__) ) . '/lang/' );
  }
}

endif;

// init the plugin
$safe_updates = Safe_Updates::init();


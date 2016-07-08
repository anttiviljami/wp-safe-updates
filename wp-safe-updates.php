<?php
/**
 * Plugin name: WP Safe Updates
 * Plugin URI: https://github.com/anttiviljami/wp-safe-updates
 * Description: Test WordPress plugin updates safely before applying them on the live site.
 * Version: 1.0.1
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
  <?php $configure_action = 'https://wordpress.org/plugins/wp-safe-updates/installation/'; ?>
  <p><?php echo wp_sprintf( __('WP Safe Updates is not yet active. Please <a href="%s" target="_blank">configure</a> it.', 'wp-safe-updates'), $configure_action ); ?> <button type="button" class="notice-dismiss"></button></p>
</div>
<?php
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


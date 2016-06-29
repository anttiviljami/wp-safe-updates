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

  public static function init() {
    if ( is_null( self::$instance ) ) {
      self::$instance = new Safe_Updates();
    }
    return self::$instance;
  }

  private function __construct() {
    // load textdomain for translations
    add_action( 'plugins_loaded',  array( $this, 'load_our_textdomain' ) );
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


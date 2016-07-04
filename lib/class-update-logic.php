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
    add_filter('gettext', array( $this, 'hack_plugin_update_text'), 10, 3 );
  }

  /**
   * HACK: filter the gettext value of the update string for a plugin
   */
  public function hack_plugin_update_text( $translated_text, $untranslated_text, $domain ) {
    if( $untranslated_text === 'There is a new version of %1$s available. <a href="%2$s" class="thickbox open-plugin-details-modal" aria-label="%3$s">View version %4$s details</a> or <a href="%5$s" class="update-link" aria-label="%6$s">update now</a>.' ) {
      return __('There is a new version of %1$s available. <a href="%2$s" class="thickbox open-plugin-details-modal" aria-label="%3$s">View version %4$s details</a>, <a href="%5$s&alt_heap=test">test update</a> or <a href="%5$s" class="update-link" aria-label="%6$s">update now</a>.');
    }
    return $translated_text;
  }
}


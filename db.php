<?php

/**
 * Extend $wpdb to allow for cookie based alternative db prefixes
 */
class safe_wpdb extends wpdb {
  public function set_prefix( $prefix, $set_table_names = true ) {
    if( function_exists( 'currheap' ) && false !== currheap() ) {
      $alt_db_prefix = 'tmp_' . currheap() . '_';
      $prefix = $prefix . $alt_db_prefix; // wp_tmp_{_alt_heap}_
    }

    // set up the prefix globally and set up all the tables
    parent::set_prefix( $prefix, $set_table_names );

    if( function_exists( 'currheap' ) && false !== currheap() ) {
      // bail out early if wordpress isn't installed

      // check if siteurl is available
      $siteurl = $this->get_var( "SELECT option_value FROM $this->options WHERE option_name='siteurl'" );
      header('X-Siteurl:' . $this->options);
      if( null === $siteurl ) {
        // it's not, let's bail out...
        // clear the alt_heap cookie
        setcookie('_alt_heap', '', 0, '/');

        // reload the page
        // Note: wp_redirect isn't set yet, so we do it manually
        $request_uri = $_SERVER['REQUEST_URI'];
        header('Location:' . $request_uri);
        http_response_code( 302 );
        exit;
      }
    }
  }
}

// use our wpdb as the global one
$wpdb = new safe_wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );


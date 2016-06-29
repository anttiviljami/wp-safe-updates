<?php

/**
 * Extend $wpdb to allow for cookie based alternative db prefixes
 */
class safe_wpdb extends wpdb {
  public function set_prefix( $prefix, $set_table_names = true ) {
    // use the contents of _alt_heap cookie, if it exists, as an additional db
    // prefix ...suffix :)
    if( isset( $_COOKIE['_alt_heap'] ) && ! empty( $_COOKIE['_alt_heap'] ) ) {
      $alt_db_prefix = 'tmp_' . $_COOKIE['_alt_heap'] . '_';
      $prefix = $prefix . $alt_db_prefix; // wp_tmp_{_alt_heap}_
    }
    parent::set_prefix( $prefix, $set_table_names );
  }
}

// use our wpdb as the global one
$wpdb = new safe_wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );


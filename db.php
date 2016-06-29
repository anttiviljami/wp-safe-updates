<?php

/**
 * Extend $wpdb to allow for cookie based alternative db prefixes
 */
class safe_wpdb extends wpdb {
  public function set_prefix( $prefix, $set_table_names = true ) {
    if( false !== currheap() ) {
      $alt_db_prefix = 'tmp_' . currheap() . '_';
      $prefix = $prefix . $alt_db_prefix; // wp_tmp_{_alt_heap}_
    }
    parent::set_prefix( $prefix, $set_table_names );
  }
}

// use our wpdb as the global one
$wpdb = new safe_wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );


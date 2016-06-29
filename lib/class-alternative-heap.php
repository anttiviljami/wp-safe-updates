<?php

/**
 * Modifies the current heap to fork some functionalities like which database
 * prefix is in use. This is used to create safe, non-public temporary
 * environments to test updates.
 */
class Alternative_Heap {
  public function __construct() {
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
   * Clones all WordPress tables
   */
  public function clone_wp_tables( $alt_heap ) {
    global $wpdb;

    $old_prefix = $wpdb->prefix;
    $alt_prefix = self::get_alt_prefix( $alt_heap );

    $tables = self::get_wp_tables();
    foreach ( $tables as $table ) {
      $newtable = str_ireplace( $old_prefix, $alt_prefix, $table );
      $query = wp_sprintf( "DROP TABLE IF EXISTS %s;", $newtable );
      $wpdb->query( $query );
      $query = wp_sprintf( "CREATE TABLE %s LIKE %s;", $newtable, $table );
      $wpdb->query( $query );
      $query = wp_sprintf( "INSERT %s SELECT * FROM %s;", $newtable, $table );
      $wpdb->query( $query );
    }
  }

  /**
   * Delete temp tables
   */
  public function delete_tmp_wp_tables( $alt_heap = "" ) {
    global $wpdb;

    $tables = self::get_tmp_wp_tables( $alt_heap );

    // extra step: make sure no original wp tables are included in the $tables array
    // there is no logical way for this to ever happen, but i feel better putting this here
    $tables = array_diff( $tables, self::get_wp_tables() );

    // still, please back up databases, ok? :)

    foreach ( $tables as $table ) {
      $query = wp_sprintf( "DROP TABLE IF EXISTS %s;", $table );
      $wpdb->query( $query );
    }
  }
}


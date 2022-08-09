<?php
/**
 * YRMS Booking Class
 *
 * @package YRMS-Booking
 * @subpackage Class
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * YRMS Booking class.
 *
 * Handles populating and saving yrms booking relationships.
 *
 * @since 1.0.0
 */
class YRMS_Booking {
  /**
   * Upsert accomodation types into the database
   * 
   * @param array $rows
   * @since 1.0.0
   */
  static function upsertAccomodationTypes(array $rows) {
    return self::upsertAccomodation($rows, 'mphb_room_type');
  }

  /**
   * Upsert accomodation rooms into the database
   * 
   * @param array $rows
   * @since 1.0.0
   */
  static function upsertAccomodationRooms(array $rows) {
    return self::upsertAccomodation($rows, 'mphb_room');
  }

  /**
   * Upsert accomodation rates into the database
   * 
   * @param array $rows
   * @since 1.0.0
   */
  static function upsertAccomodationSeasons(array $rows) {
    return self::upsertAccomodation($rows, 'mphb_season');
  }

  /**
   * Upsert accomodation rates into the database
   * 
   * @param array $rows
   * @since 1.0.0
   */
  static function upsertAccomodationRates(array $rows) {
    return self::upsertAccomodation($rows, 'mphb_rate');
  }

  /**
   * Upsert post into the database
   * 
   * @param array $rows
   * @since 1.0.0
   */
  static function upsertAccomodation(array $rows, $post_type) {
    global $wpdb;

    $data = array();
    $values = array();
    $place_holders = array();
    $rms_data = array();
    $accommodation = 'mphb_room' == $post_type;

    $ID = $wpdb->get_var( "SELECT id FROM $wpdb->posts ORDER BY id DESC LIMIT 1");

    $query = "INSERT INTO $wpdb->posts
    ( 
      ID,
      post_author, 
      post_date, 
      post_date_gmt, 
      post_content, 
      post_title, 
      post_excerpt,
      comment_status,
      ping_status,
      post_name,
      to_ping,
      pinged,
      post_modified,
      post_modified_gmt,
      post_content_filtered,
      guid,
      post_type
    ) VALUES ";
     
    foreach($rows as $item) {
			// cast to object/stdClass
			$item = (object) $item;

      // skip if empty
      if ( !isset( $item->id ) || !isset( $item->name ) ) {
        continue;
      }

      // increment id
      $ID++;

      // push
      $data[] = [
        'ID' => $ID,
        'id' => $item->id,
        'name' => $item->name ?? null,
        'categoryId' => $item->categoryId ?? null,
        'dateFrom' => $item->periodFrom ?? null,
        'dateTo' => $item->periodTo ?? null
      ];
      $rms_data[$item->id] = $ID;

      $place_holders[] = "('%d', '%d', %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)";
      array_push( 
        $values, 
        $ID,
        get_current_user_id(), 
        current_time( 'mysql' ),
        current_time( 'mysql', 1 ),
        $item->longDescription ?? '', 
        $item->name, 
        $item->headline ?? '',
        $post_type == 'mphb_room_type' ? 'open': 'closed',
        'closed',
        $post_type == 'mphb_season' ? $ID: sanitize_title($item->name),
        '',
        '',
        current_time( 'mysql' ),
        current_time( 'mysql', 1 ),
        '',
        !$accommodation 
          ? home_url("?post_type=$post_type#038;p=$ID")
          : '',
        $post_type
      );
    }

    $query .= implode( ', ', $place_holders );

    // if no record to insert, return
    if ( empty( $place_holders ) ) {
			return [];
		}

    // delete
    $placeholders = implode( ', ',  array_fill(0, count($data), '%s') );
    $sql = "DELETE FROM $wpdb->posts WHERE (post_title) IN ($placeholders)";
    $wpdb->query($wpdb->prepare($sql, array_column($data, 'name') ));

    // insert
    $wpdb->query( $wpdb->prepare( "$query", $values ) );
  
    // save categories
    update_option("yrms_$post_type"."s", $rms_data);

    // result
    return $data;
  }
}

/**
 * Create Accomodation rates Metas
 * @param array $data
 * @return int
 */
function yrms_create_update_accomodation_rates_meta($rows) {
  global $wpdb;

  $values = array();
  $place_holders = array();

  $query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES ";

  foreach($rows as $item) {
    // cast to object/stdClass
    $item = (object) $item;

    // skip if empty
    if ( !isset( $item->ratePostId ) && !isset( $item->categoryPostId ) ) {
      continue;
    }

    $place_holders[] = "('%d', %s, %s), ('%d', %s, %s)";
    array_push( 
      $values, 
      $item->ratePostId,
      'mphb_season_prices',
      maybe_serialize(array(
        array(
          "season" => "$item->periodPostId",
          "price" => array(
            "periods" => array(1),
            "prices" => array($item->dailyRate),
            "enable_variations" => !1,
            "variations" => array()
          ),
        )
      ))
    );

    array_push( 
      $values, 
      $item->ratePostId,
      'mphb_description',
      ''
    );
  }

  $query .= implode( ', ', $place_holders );

  // if no record to insert, return
  if ( empty( $place_holders ) ) {
    return false;
  }

  // delete
  $sql = "DELETE FROM $wpdb->postmeta WHERE post_id NOT IN (SELECT post.ID FROM $wpdb->posts post) AND (meta_key = %s OR meta_key = %s OR meta_key = %s OR meta_key = %s)";
  $wpdb->query( $wpdb->prepare( "$sql", ['mphb_season_prices', 'mphb_description', '_edit_last', '_edit_lock'] ) );
  
  // insert
  return $wpdb->query( $wpdb->prepare( "$query", $values ) );
}

/**
 * Create Accomodation Type Metas
 * @param array $data
 * @return int
 */
function yrms_create_update_accomodation_rooms_meta($rows) {
  
  global $wpdb;

  $values = array();
  $place_holders = array();
  $categories = get_option('yrms_mphb_room_types');
	$areaCategories = array();

  $query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES ";

  foreach($rows as $item) {
    // cast to object/stdClass
    $item = (object) $item;

    // skip if empty
    if ( !isset( $item->ID ) && !isset( $item->name ) ) {
      continue;
    }

    if(isset($item->id)) {
      // push
      $areaCategories[$item->id] = $item->categoryId;
    }
    
    $place_holders[] = "('%d', %s, %s)";
    array_push( 
      $values, 
      $item->ID,
      'mphb_room_type_id',
      $categories[$item->categoryId]
    );

  }

  $query .= implode( ', ', $place_holders );

  // if no record to insert, return
  if ( empty( $place_holders ) ) {
    return false;
  }

  // delete
  $sql = "DELETE FROM $wpdb->postmeta WHERE post_id NOT IN (SELECT post.ID FROM $wpdb->posts post) AND meta_key = %s";
  $wpdb->query( $wpdb->prepare( "$sql", ['mphb_room_type_id'] ) );

  // insert area categories
  update_option("yrms_area_categories", $areaCategories);

  // insert
  return $wpdb->query( $wpdb->prepare( "$query", $values ) );
}

/**
 * Create Accomodation Season Metas
 * @param array $data
 * @return int
 */
function yrms_create_update_accomodation_seasons_meta($rows) {
  global $wpdb;

  $values = array();
  $place_holders = array();
  $query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES ";

  foreach($rows as $item) {
    // cast to object/stdClass
    $item = (object) $item;

    // skip if empty
    if ( !isset( $item->ID ) || !isset( $item->name ) ) {
      continue;
    }

    $place_holders[] = "('%d', %s, %s), ('%d', %s, %s), ('%d', %s, %s)";
    array_push( 
      $values, 
      $item->ID,
      'mphb_start_date',
      explode(' ',$item->dateFrom)[0]
    );

    array_push( 
      $values, 
      $item->ID,
      'mphb_end_date',
      explode(' ',$item->dateTo)[0]
    );

    array_push( 
      $values, 
      $item->ID,
      'mphb_days',
      maybe_serialize(["0", "1", "2", "3", "4", "5", "6"])
    );
  }

  $query .= implode( ', ', $place_holders );

  // if no record to insert, return
  if ( empty( $place_holders ) ) {
    return false;
  }

  // delete
  $sql = "DELETE FROM $wpdb->postmeta WHERE post_id NOT IN (SELECT post.ID FROM $wpdb->posts post) AND (meta_key = %s OR meta_key = %s OR meta_key = %s)";
  $wpdb->query( $wpdb->prepare( "$sql", ['mphb_start_date', 'mphb_end_date', 'mphb_days'] ) );

  // insert
  return $wpdb->query( $wpdb->prepare( "$query", $values ) );
}


<?php

/**
 * YRMS Booking Shortcodes
 *
 * @package YRMS-Booking
 * @subpackage Shortcodes
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action('mphb_sc_search_results_before_search', function() {
  // get request
  $input = $_GET;

  $areas = yrms_request('availableAreas', [
    "adults" => $input['mphb_adults'],
    "agentId" => 1,
    "categoryIds" => get_option('yrms_categoies', []),
    "children" => $input['mphb_children'],
    "dateFrom" => "{$input['mphb_check_in_date']} 00:00:00",
    "dateTo" => "{$input['mphb_check_out_date']} 00:00:00",
    "infants" => 0,
    "propertyId" => get_option('yrms_property_id')
  ]);
   
  // insert rms failities into database
  yrms_insert_facilities_as_accomodations($areas);
});
<?php

/**
 * YRMS Booking Filters
 *
 * @package YRMS-Booking
 * @subpackage Actions
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// filter path to templates
add_filter('mphb_template_path', function() {
  return "../../plugins/" . basename( YRMS_BOOKING_DIR ) . "/_templates/";
});

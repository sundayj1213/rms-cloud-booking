<?php

/**
 * YRMS Booking Actions
 *
 * @package YRMS-Booking
 * @subpackage Actions
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// hook into booking confirmed action
add_action('mphb_booking_confirmed', function(\MPHB\Entities\Booking $booking) {
  // create reservation on RMS
  yrms_create_reservation($booking);
});

// hook into search results action
add_action('mphb_sc_search_results_before_search', function() {
  // sync data
  yrms_sync_rms($_GET);
});

<?php
/*
  Plugin Name: Yoodule RMS Booking
  Description: Import and Create RMS bookings
  Version: 1.5
  Author: Yoodule
  Author URI: https://www.yoodule.com
  License: GPLv2 or later
  License URI: http://www.gnu.org/licenses/gpl-2.0.html
  Text Domain: yoodule-rms-booking
  Domain Path: /languages
*/

/**
 * YRMS Booking
 *
 * @package YRMS-Booking
 * @subpackage Loader
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// define global variable
$yrmsBooking = new stdClass;

// some pertinent defines.
define( 'YRMS_BOOKING_DIR', dirname( __FILE__ ) );
define( 'YRMS_BOOKING_URL', plugins_url( basename( YRMS_BOOKING_DIR ) ) . '/' );

/**
 * Only load the plugin code if BuddyPress is activated.
 */
function yrms_booking_init() {
  
  require( constant( 'YRMS_BOOKING_DIR' ) . '/yrms-booking-core.php' );

	// init
	$GLOBALS['yrmsBooking'] = new YRMS_Booking_Core();
  

	// Load up the updater if we're in the admin area
	//
	// Checking the WP_NETWORK_ADMIN define is a more, reliable check to determine
	// if we're in the admin area.
	if ( defined( 'WP_NETWORK_ADMIN' ) ) {
		$GLOBALS['yrmsBooking']->updater = new YRMS_Booking_Updater();
	}
}

add_action( 'init', 'yrms_booking_init' );
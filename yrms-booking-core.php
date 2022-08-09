<?php
/**
 * YRMS Booking
 *
 * @package YRMS-Booking
 * @subpackage Core
 */

 // Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
	require_once dirname(__FILE__) . '/vendor/autoload.php';
}


/**
 * Core class for YRMS Booking
 * 
 * @package YRMS-Booking
 * @subpackage Classes
 *
 * @since 1.2
 */
class YRMS_Booking_Core {

  /**
	 * The path to plugin files
	 *
	 * @since 1.5.0
	 * @var string $path
	 */
	public $path = '';

  /**
	 * The table_name used by plugin
	 *
	 * @since 1.5.0
	 * @var string $table_name
	 */
	public $table_name = '';

  /**
	 * Constructor.
	 */
	public function __construct() {
		
    // include our files.
		$this->includes();

    // set globals
    $this->setup_globals();
  }

  /**
	 * Includes.
	 */
	public function includes( ) {

    // Path for includes.
		$this->path = constant( 'YRMS_BOOKING_DIR' ) . '/_inc';

		/** Core **************************************************************/
		require( $this->path . '/yrms-booking-classes.php' );
		require( $this->path . '/yrms-booking-actions.php' );
		require( $this->path . '/yrms-booking-filters.php' );
		require( $this->path . '/yrms-booking-functions.php' );

		// Load AJAX code when an AJAX request is requested.
    add_action( 'admin_init', function() {
      if ( defined( 'DOING_AJAX' ) && true === DOING_AJAX && isset( $_POST['action'] ) && false !== strpos( $_POST['action'], 'yrms_booking' ) ) {
        require $this->path . '/yrms-booking-ajax.php';
      }
    } );

    // updater.
		if ( defined( 'WP_NETWORK_ADMIN' ) ) {
			require( $this->path . '/yrms-booking-updater.php' );
		}
	}

  /**
	 * Setup globals.
	 *
	 * @since 1.3.0 Add 'global' properties
	 */
	public function setup_globals( ) {
		
		// set properties
    $this->agentId = 15;
		$this->agentPassword = '1h&29$vk449f8';
		$this->clientId = '11281';
		$this->clientPassword = '6k!Dp$N4';

		// javascript hook.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 11 );
  }
  
  /**
	 * Enqueues the javascript.
	 *
	 * The JS is used to add AJAX functionality when clicking on the subscribe button.
	 */
	public function enqueue_scripts() {

		// Do not enqueue if no user is logged in.
		if ( ! is_user_logged_in() ) {
			return;
		}

		wp_enqueue_script( 'yrms-booking-js', constant( 'YRMS_BOOKING_URL' ) . '/_assets/js/yrms-booking.js', array( 'jquery' ), time() );
    wp_enqueue_style( 'yrms-booking-css', constant( 'YRMS_BOOKING_URL' ) . '/_assets/css/yrms-booking.css?v='.time() );
	}
}

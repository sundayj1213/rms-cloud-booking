<?php
/**
 * YRMS Booking Updater
 *
 * @package YRMS-Booking
 * @subpackage Updater
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Updater class.
 *
 * @since 1.3.0
 */
class YRMS_Booking_Updater {

	/**
	 * Constructor.
	 *
	 * Only load our updater on certain admin pages only.  This currently includes
	 * the "Dashboard", "Dashboard > Updates" and "Plugins" pages.
	 */
	public function __construct() {
		add_action( 'load-plugins.php',     array( $this, '_init' ) );
	}

	/**
	 * Stub initializer.
	 *
	 * This is designed to prevent access to the main, protected init method.
	 */
	public function _init() {
		if ( ! did_action( 'admin_init' ) ) {
			return;
		}

    $this->install();
	}

	/** INSTALL *******************************************************/

	/**
	 * Installs the YRMS Booking DB table.
	 */
	protected function install() {
		// header
		$headers = array(
			'authtoken' => yrms_get_auth_token()
		);

		// create or update accomodation types
		yrms_create_update_accomodation_types(
			$headers
		);
	}
}

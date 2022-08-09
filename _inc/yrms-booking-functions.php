<?php

/**
 * YRMS Booking Functions
 *
 * @package YRMS-Booking
 * @subpackage Functions
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Locate template.
 * @since 1.0.0
 *
 * @param 	string 	$template_name			Template to load.
 * @param 	string 	$string $template_path	Path to templates.
 * @param 	string	$default_path			Default path to template files.
 * @return 	string 							Path to the template file.
 */
function yrms_booking_locate_template( $template_name, $template_path = '', $default_path = '' ) {

	// Set default plugin templates path.
	if ( ! $default_path ) :
		$default_path = constant( 'YRMS_DATATABLE_DIR' ) . '/_views'; // Path to the template folder
	endif;
		
  $template = $default_path . $template_name;

	return apply_filters( 'yrms_booking_locate_template', $template, $template_name, $template_path, $default_path );
}


/**
 * Get template.
 *
 * Search for the template and include the file.
 *
 * @since 1.0.0
 *
 * @see yrms_booking_locate_template()
 *
 * @param string 	$template_name			Template to load.
 * @param array 	$args					Args passed for the template file.
 * @param string 	$string $template_path	Path to templates.
 * @param string	$default_path			Default path to template files.
 */
function yrms_booking_get_template( $template_name, $args = array(), $tempate_path = '', $default_path = '' ) {

	if ( is_array( $args ) && isset( $args ) ) :
		extract( $args );
	endif;

	$template_file = yrms_booking_locate_template( $template_name, $tempate_path, $default_path );

	if ( ! file_exists( $template_file ) ) :
		_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $template_file ), '1.0.0' );
		return;
	endif;

	ob_start();
	  include $template_file;
  return ob_get_clean();
}

function chunck_array($arr, $string = true) {
	$string_columns = array_filter($arr, function($item) use ($string) {
    return $string ? is_string($item): is_object($item);
  });
  $rows = ceil(count($string_columns) / 10);
  return array_chunk($string_columns, $rows, true);
}

/**
 * Create or retrieve client token
 * @return string
 */
function yrms_get_auth_token() {

	$response = yrms_request('properties', [
		"modelType=" => "full"
	], "GET");
	 
	if(!$response) {
		return yrms_generate_auth_token();
	}

	// set property id
	yrms_set_client_property($response);

	// return token
	return get_option('yrms_auth_token');
}

/**
 * Sets client property ID
 * @return string
 */
function yrms_set_client_property(array $properties, $clientId = 11282) {
	$property = yrms_find_object($properties, 'clientId', $clientId);
	// update
	update_option('yrms_property_id', $property->id);
	// return object
	return $property;
}

/**
 * Find Array Object by value
 */
function yrms_find_object($array, $key, $value){

	foreach ( $array as $element ) {
		if ( isset($element->{$key}) && $value == $element->{$key} ) {
			return $element;
		}
	}

	return false;
}

/**
 * Generate RMS token
 * @return string
 */
function yrms_generate_auth_token() {
	global $yrmsBooking;

	// get token
	$response = (array) yrms_request('authToken', [
		"agentId" => $yrmsBooking->agentId,
    "agentPassword" => $yrmsBooking->agentPassword,
    "clientId" => $yrmsBooking->clientId,
    "clientPassword" => $yrmsBooking->clientPassword,
    "useTrainingDatabase" => false,
    "moduleType" => ["distribution"]
	]);
	// update
	update_option('yrms_auth_token', $response["token"]);
	// return string
	return $response["token"];
}

/**
 * RMS POST request
 * @return array
 */
function yrms_request($endPoint, $data, $method = "POST", $headers = array() ) {
	// format data
	$data = $method === "GET" ? $data: json_encode($data);
	// make request
	$res = wp_remote_request( "https://restapi8.rmscloud.com/$endPoint", array(
    'method'      => $method,
    'timeout'     => 45,
    'headers'     => array_merge(array(
			'Content-Type' => 'application/json',
			'authtoken' => get_option('yrms_auth_token')
		), $headers),
    'body'        => $data,
    )
	);	 
	//Check for success
  if(!is_wp_error($res) && ($res['response']['code'] == 200 || $res['response']['code'] == 201)) {
    return json_decode($res['body']);
  }

	// return false
	return false;
}

/**
 * Assign RMS prices to rates
 * @return void
 */
function yrms_assign_price_to_rates($facilities, $categories) {
	// rates
	$rates = get_option('yrms_mphb_rates', []);	
	// map rates grid to db
	yrms_map_rates_grid(
		$facilities,
		$categories,
		$rates
	);
} 

/**
 * Map Rates Grid to DB
 * @param array $ratesGrid
 * @param array $categories
 * @param array $rates
 * @return void
 */
function yrms_map_rates_grid($ratesGrid, $categories, $rates) {
	$data = array();
	$seasons = get_option('yrms_mphb_seasons');
	$categoryPeriods = get_option('yrms_category_periods');
	
	foreach($ratesGrid as $item) {

		foreach($item->areas as $area) {
			 
			$dailyRate = isset($area->availability) &&  isset($area->availability[0]->rate)
				? $area->availability[0]->rate: 0;

			$data[] = [
				'dailyRate' => $dailyRate,
				'categoryId' => $item->categoryId,
				'categoryPostId' => $categories[$item->categoryId] ?? 0,
				'ratePostId' => $rates[$item->rateId] ?? 0,
				'rateId' => $item->rateId,
				'periodId' => $categoryPeriods[$item->categoryId] ?? 0,
				'periodPostId' => $seasons[$categoryPeriods[$item->categoryId]??0]
			];
		}
	}


	// create meta for rates, assigned to season
	yrms_create_update_accomodation_rates_meta($data);
}

/**
 * Assign RMS rates to accomodations
 * @return void
 */
function yrms_assign_rates_to_accomodations($rows) {
	// rates
	$rates = get_option('yrms_mphb_rates', []);

	// create new key
	foreach($rows as $item) $item->ID = $rates[$item->rateId];
	
	// create meta for rates, assigned to post type
	yrms_create_update_accomodation_rooms_meta($rows);
}
/**
 * Insert RMS areas into database
 * @return void
 */
function yrms_insert_areas_as_accomodations($input) {

	// get areas
  $areas = yrms_request('availableAreas', [
    "categoryIds" => array_keys(get_option('yrms_mphb_room_types', [])),
    "dateFrom" => "{$input['mphb_check_in_date']} 00:00:00",
    "dateTo" => "{$input['mphb_check_out_date']} 00:00:00",
  ]);

	// if object & not empty proceed
	if(!is_array($areas) || empty($areas)) return;
	
	// create rms rooms
	$data = YRMS_Booking::upsertAccomodationRooms($areas);

	// create rooms meta
	yrms_create_update_accomodation_rooms_meta($data);
}

/**
 * Create or Update Accomodation types
 * @return array
 */
function yrms_create_update_accomodation_types( $headers = array() ) {	
	// get categories
	$categories = yrms_fetch_categories($headers);
	 
	// insert all categories
	YRMS_Booking::upsertAccomodationTypes( $categories );
}

/**
 * Sync RMS data
 * @return void
 */
function yrms_sync_rms($input) {
	global $yrmsBooking;

	if(!isset($input['mphb_check_in_date']) || !isset($input['mphb_check_in_date'])) return;

	// create or update accomodation rates
	$facilities = yrms_create_update_accomodation_rates($input);

	// categories
	$categories = get_option('yrms_mphb_room_types', []);

	// get rates
	$rows = yrms_fetch_paginate(
		'rates/lookups/search', 
		array(
			"adults" => $input['mphb_adults'],
			"agentId" => $yrmsBooking->agentId,
      "categoryIds" => array_keys($categories),
			"children" => $input['mphb_children'],
			"dateFrom" => "{$input['mphb_check_in_date']} 00:00:00",
      "dateTo" => "{$input['mphb_check_out_date']} 00:00:00",
			"infants" => 0,
			"propertyId" => get_option('yrms_property_id')
		)
	);
	
	// create or update accomodation seasons
  yrms_create_update_accomodation_seasons($rows);

	// map rates to accomodation
  yrms_assign_rates_to_accomodations($facilities);

	// asign price to rates
	yrms_assign_price_to_rates($facilities, $categories);

	// insert rms areas into database
  yrms_insert_areas_as_accomodations($input);
}

/**
 * Create or Update Accomodation seasons
 * @return array
 */
function yrms_create_update_accomodation_seasons( $rows ) {	
	// data
	$data = array();
	// rate periods
	$categoryPeriods = array();

	// create new key
	foreach($rows as $item) {
		// push
		$categoryPeriods[$item->categoryId] = $item->periodId;

		if(in_array($item->periodId, array_column($data, 'periodId'))) {
			continue;
		}

		// map name
		$item->name = $item->periodDescription;
		// map id
		$item->id = $item->periodId;

		// push
		$data[] = $item;
		
	}
	
	// insert rate periods
	update_option("yrms_category_periods", $categoryPeriods);

	// insert all seasons
	$data = YRMS_Booking::upsertAccomodationSeasons( $data );

	// create seasons meta
	yrms_create_update_accomodation_seasons_meta($data);

}

/**
 * Create or Update Accomodation rates
 * @return array
 */
function yrms_create_update_accomodation_rates( $input ) {	

	global $yrmsBooking;

	// categories
	$categories = get_option('yrms_mphb_room_types', []);

	// lookup rates
	$rows = (array) yrms_request(
		'availableFacilities', 
		array(
			"adults" => $input['mphb_adults'],
			"agentId" => $yrmsBooking->agentId,
      "categoryIds" => array_keys($categories),
			"children" => $input['mphb_children'],
			"dateFrom" => "{$input['mphb_check_in_date']} 00:00:00",
      "dateTo" => "{$input['mphb_check_out_date']} 00:00:00",
			"infants" => 0,
			"propertyId" => get_option('yrms_property_id'),
		)
	);
	$facilities = $rows['facilities'] ?? [];
	// data
	$data = array();

	// create new key
	foreach($facilities as $item) {

		if(in_array($item->rateId, array_column($data, 'rateId'))) {
			continue;
		}

		// map name
		$item->name = $item->rateName;
		// map id
		$item->id = $item->rateId;

		// push
		$data[] = $item;
	}

	// insert all rates
	YRMS_Booking::upsertAccomodationRates( $data );

	// return
	return $facilities;
}

/**
 * Fetch categories
 * @return array
 */
function yrms_fetch_categories( $headers = array(), $method = "GET" ) {
	// get categories
	return yrms_fetch_paginate(
		'categories', 
		array(
			"limit" => 500,
			"propertyId" => get_option('yrms_property_id'),
			"modelType" => "full",
		),
		$method,
		$headers
	);
}

/**
 * Fetch seasons
 * @return array
 */
function yrms_fetch_seasons( $headers = array(), $method = "GET" ) {
	// get periods
	return yrms_fetch_paginate(
		'rates/periods', 
		array(
			'limit' => 500
		),
		$method,
		$headers
	);
}

/**
 * Fetch rates
 * @return array
 */
function yrms_fetch_rates($headers = array(), $method = "GET" ) {
	// get rates
	return yrms_fetch_paginate(
		'rates', 
		array(
			'limit' => 500
		),
		$method,
		$headers
	);
}

/**
 * Fetch categories
 * @return array
 */
function yrms_fetch_paginate( $endPoint, $data = array(), $method = "POST", $headers = array(), $output = array(), $offset = 0 ) {
	
	// query
	$query = ("POST" === $method ? "?limit=500&offset=".($offset * 500):"");
	// get result
	$result = yrms_request($endPoint . $query, array_merge($data, [
		"offset" => ($offset * 500)
	]), $method, $headers);
	 
	// if empty return data
	if(!$result) return [];
	
	// push categories array
	foreach($result as $item) $output[] = $item;

	// increment
	$offset++;

	// more
	$more = yrms_fetch_paginate($endPoint, $data, $method, $headers, $output, $offset);

	// if empty return
	if(empty((array) $more)) return $output;

	// push into array
	foreach($more as $item) $output[] = $item;

	// increment
	$offset++;

	// return more
	return yrms_fetch_paginate(
		$endPoint,
		$data,
		$method,
		$headers,
		$output,
		$offset
	);
}

/**
 * Create Reservation on RMS
 */
function yrms_create_reservation(\MPHB\Entities\Booking $booking) {
	$reservations = array();
	$rooms = array_flip(get_option('yrms_mphb_rooms', []));	
  $areaCategories = get_option('yrms_area_categories', []);

  $guest = (object) yrms_request('guests?ignoreMandatoryFieldWarnings=true', [
    "id" => 0,
    "addressLine1" => $booking->getCustomer()->getAddress1(),
    "email" => $booking->getCustomer()->getEmail(),
    "emailOptOut" => !0,
    "marketingOptOut"  => !0,
    "phoneOptOut" => !0,
    "smsOptOut" => !0,
    "mobile" => $booking->getCustomer()->getPhone(),
    "guestGiven" => $booking->getCustomer()->getFirstName(),
    "guestSurname" => $booking->getCustomer()->getLastName(),
    "languageSpoken" => $booking->getLanguage(),
    "propertyId" => get_option('yrms_property_id')
  ]);

  // create guests
  foreach($booking->getReservedRooms() as $item) {
    // create
    $result = (object) yrms_request('reservations?ignoreMandatoryFieldWarnings=true', [
      "adults" => $item->getAdults(),
      "areaId" => $rooms[$item->getRoomId()] ?? null,
      "arrivalDate" => $booking->getCheckInDate()->format('Y-m-d H:i:s'),
      "categoryId" => isset($rooms[$item->getRoomId()]) ? $areaCategories[$rooms[$item->getRoomId()]] ?? null: null,
      "children" => $item->getChildren(),
      "departureDate" => $booking->getCheckOutDate()->format('Y-m-d H:i:s'),
      "guestId" => $guest->id,
      "infants" => 0,
      "notes" => $booking->getNote()
    ]);

    // confirm
    $reservations[] = yrms_request("reservations/".($result->id??0)."/status", [
      "status" => "Confirmed"
    ], "PUT");
  }  
}
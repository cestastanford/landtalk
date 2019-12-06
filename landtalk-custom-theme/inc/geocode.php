<?php
/**
 * Adds functionality to retrieve results from the MapQuest geocoding
 * service.
 *
 * @package Land Talk Custom Theme
 */

/**
 * Given an address, performs a forward-geocoding request to the
 * MapQuest API to get full address matches and their respective
 * lat/lng coordinates.  Returns an array of {address, latitude, longitude}
 * objects.  If coordinate pair given as input, that coordinate pair
 * is returned with its closest address equivalent with `inputCoordinates`
 * set to `true`.
 *
 * @param string $input_address The input string to geocode.
 * @param int    $n_results The numbers of results to return.
 */
function landtalk_geocode( $input_address, $n_results ) {

	// Performs request.
	$url                  = 'http://www.mapquestapi.com/geocoding/v1/address';
	$url                 .= '?key=' . MAPQUEST_API_KEY;
	$url                 .= '&location=' . rawurlencode( $input_address );
	$url                 .= '&maxResults=' . $n_results;
	$response             = wp_remote_get( $url );
	$response_body        = wp_remote_retrieve_body( $response );
	$parsed_response_body = json_decode( $response_body, true );
	$locations            = $parsed_response_body['results'][0]['locations'];
	$provided_location    = $parsed_response_body['results'][0]['providedLocation'];

	// Prepares location objects for return.
	$prepared_locations = array_map(
		function( $location ) {

			$address = implode(
				array_filter(
					array_map(
						function( $component_key ) use ( $location ) {
							return $location[ $component_key ];
						},
						array( 'street', 'adminArea5', 'adminArea3', 'adminArea1' )
					),
					function( $component_value ) {
						return ! empty( $component_value );
					}
				),
				', '
			);

			return array(
				'address'   => $address,
				'latitude'  => $location['displayLatLng']['lat'],
				'longitude' => $location['displayLatLng']['lng'],
			);

		},
		$locations
	);

	// If coordinate pair provided instead of address.
	if ( isset( $provided_location['latLng'] ) ) {
		$prepared_locations[0]['latitude']         = $provided_location['latLng']['lat'];
		$prepared_locations[0]['longitude']        = $provided_location['latLng']['lng'];
		$prepared_locations[0]['inputCoordinates'] = true;
	}

	// Returns prepared locations.
	return $prepared_locations;

}

<?php
/**
 * Functions for calculating distances.
 *
 * @package Land Talk Custom Theme
 */

/**
 * Slightly-modified function that calculates distance on Earth
 * between two lat/lng pairs.  Source: https://stackoverflow.com/a/10054282
 *
 * @param float $latitude_from The latitude of the first location.
 * @param float $longitude_from The longitude of the first location.
 * @param float $latitude_to The latitude of the second location.
 * @param float $longitude_to The longitude of the second location.
 */
function landtalk_haversine_great_circle_distance(
	$latitude_from,
	$longitude_from,
	$latitude_to,
	$longitude_to
) {

	$lat_from = deg2rad( $latitude_from );
	$lon_from = deg2rad( $longitude_from );
	$lat_to   = deg2rad( $latitude_to );
	$lon_to   = deg2rad( $longitude_to );

	$lat_delta = $lat_to - $lat_from;
	$lon_delta = $lon_to - $lon_from;

	$angle = 2 * asin(
		sqrt(
			pow( sin( $lat_delta / 2 ), 2 )
			+ cos( $lat_from ) * cos( $lat_to ) * pow( sin( $lon_delta / 2 ), 2 )
		)
	);

	return $angle * EARTH_RADIUS;

}

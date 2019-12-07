<?php
/**
 * Adds support for validating submission details, including YouTube
 * embeds.
 *
 * @package Land Talk Custom Theme
 */

/**
 * Extracts YouTube ID from various link/embed formats.
 *
 * @param string $str The YouTube link/embed text.
 */
function landtalk_get_youtube_id( $str ) {
	$re      = '/(?:youtube(?:-nocookie)?\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^\"&?\/ ]{11})/';
	$matches = array();
	preg_match( $re, $str, $matches );
	if ( isset( $matches[1] ) ) {
		return $matches[1];
	}
}


/**
 * Checks if a YouTube ID corresponds to a valid YouTube video.
 *
 * @param string $id The YouTube video ID.
 */
function landtalk_check_youtube_id_valid( $id ) {
	if ( empty( $id ) ) {
		return false;
	}

	$url = add_query_arg(
		array(
			'part' => 'status',
			'id'   => $id,
			'key'  => YOUTUBE_API_KEY,
		),
		'https://www.googleapis.com/youtube/v3/videos'
	);

	$response             = wp_remote_get( $url );
	$response_body        = wp_remote_retrieve_body( $response );
	$parsed_response_body = json_decode( $response_body, true );
	if (
		! empty( $parsed_response_body )
		&& ! empty( $parsed_response_body['items'] )
		&& ! empty( $parsed_response_body['items'][0] )
		&& ! empty( $parsed_response_body['items'][0]['status'] )
		&& ! empty( $parsed_response_body['items'][0]['status']['embeddable'] )
	) {
		return true;
	}

	return false;
}


/*
* Enforces valid YouTube URL during ACF form validation.
*/

add_filter(
	'acf/validate_value/key=' . YOUTUBE_URL_FIELD_KEY,
	function( $valid, $value ) {
		if ( ! $valid ) {
			return $valid;
		}

		$id        = landtalk_get_youtube_id( $value );
		$url_valid = landtalk_check_youtube_id_valid( $id );
		if ( ! $url_valid ) {
			$valid = 'YouTube URL is invalid, not public or not embeddable.';
		}

		return $valid;
	},
	10,
	2
);

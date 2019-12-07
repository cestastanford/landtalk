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


/*
* If "Report All Invalid YouTube URLs on Update" is enabled in Options,
* all Conversations' YouTube URLs will be checked for validitiy and
* those that are not valid will be Reported (without an email sent).
*/

if ( get_field( 'report_invalid_youtube_urls', 'options' ) ) {

	update_field( 'report_invalid_youtube_urls', false, 'options' );
	$conversations_query = new WP_Query(
		array(
			'post_type'      => CONVERSATION_POST_TYPE,
			'posts_per_page' => -1,
		)
	);

	while ( $conversations_query->have_posts() ) {
		$conversations_query->the_post();
		$youtube_id = landtalk_get_youtube_id( get_field( 'youtube_url' )['url'] );
		if ( empty( landtalk_check_youtube_id_valid( $youtube_id ) ) ) {

			// Creates a new Report.
			$report_id = wp_insert_post(
				array(
					'post_type'   => REPORT_POST_TYPE,
					'post_status' => 'publish',
					'post_title'  => 'Report of Conversation ' . get_the_ID(),
				)
			);

			// Updates the Report.
			update_field( 'reason_for_report', 'Invalid YouTube URL', $report_id );
			update_field( 'more_details', 'Automatically reported using the "Report Invalid YouTube URLs" option.', $report_id );
			update_field( 'conversation', get_the_ID(), $report_id );

			// Unpublishes the Conversation.
			wp_update_post(
				array(
					'ID'          => get_the_ID(),
					'post_status' => 'pending',
				)
			);
		}
	}

	wp_reset_postdata();

}

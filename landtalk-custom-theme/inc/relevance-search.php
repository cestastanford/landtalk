<?php
/**
 * Each sub-array is a facet of the conversation's relevance score
 * for a given search term.  `relevance` is how much the facet
 * weighs into the overall relevance score (higher numbers mean
 * more relevant) and the `preprocess` function is used to save
 * the relevant content in an easily-searchable format to postmeta
 * for better performance.  Under the current algorithm, a conversation
 * with a positive match for a facet with higher relevance will
 * always score higher than a conversation without such a match,
 * no matter how many matches it has for facets with lower relevance.
 *
 * @package Land Talk Custom Theme
 */

define( 'LOCATION_RADIUS', 20.0 );
$relevance_score_facets = array(

	// Location.
	array(
		'field_key'  => 'lat_lng',
		'relevance'  => 5,
		'preprocess' => function( $post_id ) {
			$lat_lng = get_field( 'location', $post_id )['lat_lng'];
			return $lat_lng['latitude'] . ',' . $lat_lng['longitude'];
		},
		'match'      => function( $preprocessed_value, $search_term, $location ) {
			if ( isset( $location ) ) {
				$lat_lng = explode( ',', $preprocessed_value );
				$distance = landtalk_haversine_great_circle_distance(
					(float) $location['latitude'],
					(float) $location['longitude'],
					(float) $lat_lng[0],
					(float) $lat_lng[1]
				);

				return $distance < LOCATION_RADIUS;
			}
		},
	),

	// Title.
	array(
		'field_key'  => 'title',
		'relevance'  => 4,
		'preprocess' => function( $post_id ) {
			return strtolower( get_field( 'place_name', $post_id ) );
		},
	),

	// Keywords.
	array(
		'field_key'  => 'keywords',
		'relevance'  => 3,
		'preprocess' => function( $post_id ) {
			return strtolower(
				implode(
					PREPROCESSED_SEPARATOR,
					array_map(
						function( $keyword ) {
							return $keyword->name;
						},
						empty( get_field( 'keywords', $post_id ) )
							? array()
							: get_field( 'keywords', $post_id )
					)
				)
			);
		},
	),

	// Narrative.
	array(
		'field_key'  => 'narrative',
		'relevance'  => 2,
		'preprocess' => function( $post_id ) {
			return strtolower(
				implode(
					PREPROCESSED_SEPARATOR,
					array(
						get_field( 'used_to_look', $post_id ),
						get_field( 'has_changed', $post_id ),
						get_field( 'activities', $post_id ),
					)
				)
			);
		},
	),

	// Transcript.
	array(
		'field_key'  => 'transcript',
		'relevance'  => 1,
		'preprocess' => function( $post_id ) {
			return strtolower( get_field( 'transcript', $post_id ) );
		},
	),

	// Additional Information.
	array(
		'field_key'  => 'addl_info',
		'relevance'  => 0,
		'preprocess' => function( $post_id ) {
			return strtolower( get_field( 'additional_information', $post_id ) );
		},
	),

);


/**
 * Preprocesses and saves a conversation's relevance query fields
 * to postmeta whenever a post is saved.
 *
 * @param int $post_id The ID of the Conversation being preprocessed.
 */
function landtalk_save_relevance_postmeta( $post_id ) {

	global $relevance_score_facets;
	foreach ( $relevance_score_facets as $facet ) {
		update_post_meta(
			$post_id,
			RELEVANCE_POSTMETA_KEY_PREFIX . $facet['field_key'],
			$facet['preprocess']( $post_id )
		);
	}

}

add_action(
	'save_post_' . CONVERSATION_POST_TYPE,
	'landtalk_save_relevance_postmeta'
);


/**
 * Searches Conversations in multiple content areas and returns
 * an array of arrays, each containing the conversation object,
 * the ACF fields for the conversation and the calculated relevance
 * score.
 *
 * @param array  $conversations The preprocessed conversations.
 * @param string $search_term The string to match against.
 * @param bool   $order_by_relevance Whether to return results in order
 *               of relevance.
 */
function landtalk_filter_conversations_by_relevance(
	$conversations,
	$search_term,
	$order_by_relevance
) {

	// Don't allow searches for the separator.
	if ( PREPROCESSED_SEPARATOR === $search_term ) {
		return array();
	}

	// Attempt to geocode the search term.
	$geocoded_location  = null;
	$geocoded_locations = landtalk_geocode( $search_term, 1 );
	if ( ! empty( $geocoded_locations ) ) {
		$geocoded_location = $geocoded_locations[0];
	}

	// Case insensitive search.
	$lowercase_search_term = strtolower( $search_term );

	// Fetches preprocessed relevance query data for conversations..
	$preprocessed_conversations = landtalk_retrieve_relevance_postmeta(
		$conversations
	);

	// Scores each conversation based on preprocessed data.
	$scored_conversations = array_map(
		function( $conversation ) use (
			$preprocessed_conversations,
			$lowercase_search_term,
			$geocoded_location
		) {

			$score = landtalk_score_conversation_by_relevance(
				$preprocessed_conversations[ $conversation->ID ],
				$lowercase_search_term,
				$geocoded_location
			);

			return array(
				'conversation' => $conversation,
				'score'        => $score,
			);

		},
		$conversations
	);

	// Filters to only results with score > 0.
	$filtered_scored_conversations = array_filter(
		$scored_conversations,
		function( $scored_conversation ) {
			return $scored_conversation['score'] > 0;
		}
	);

	// Sorts results by descending score, if indicated.
	if ( $order_by_relevance ) {
		usort(
			$filtered_scored_conversations,
			function( $a, $b ) {
				$difference = $b['score'] - $a['score'];
				if ( 0 === $difference ) {
					return $b['conversation']->ID - $a['conversation']->ID;
				} else {
					return $difference;
				}
			}
		);
	}

	return array_map(
		function( $scored_conversation ) {
			return $scored_conversation['conversation'];
		},
		$filtered_scored_conversations
	);

}


/**
 * Retrieves preprocessed relevance query fields for the passed
 * Conversations.
 *
 * @param array $conversations The conversations to retrieve preprocessed
 *              data for.
 */
function landtalk_retrieve_relevance_postmeta( $conversations ) {

	global $relevance_score_facets;
	$preprocessed_conversations = array();
	foreach ( $conversations as $conversation ) {

		$preprocessed_conversation = array();
		foreach ( $relevance_score_facets as $facet ) {

			$meta = get_post_meta(
				$conversation->ID,
				RELEVANCE_POSTMETA_KEY_PREFIX . $facet['field_key'],
				true
			);

			$preprocessed_conversation[ $facet['field_key'] ] = $meta;

		}

		$preprocessed_conversations[ $conversation->ID ] = $preprocessed_conversation;

	}

	return $preprocessed_conversations;

}


/**
 * Calculate's a conversation's relevance to the search term using
 * the above relevance score facets.
 *
 * @param array      $preprocessed_conversation The preprocessed conversation.
 * @param string     $search_term The string to match against.
 * @param array|null $location The location to match against, in
 *                   the format returned by landtalk_geocode.
 */
function landtalk_score_conversation_by_relevance(
	$preprocessed_conversation,
	$search_term,
	$location
) {

	global $relevance_score_facets;
	return array_reduce(
		$relevance_score_facets,
		function( $prev_score, $facet ) use (
			$preprocessed_conversation,
			$search_term,
			$location
		) {

			$next_score = $prev_score;
			if ( isset( $facet['match'] ) ) {
				$match = $facet['match'](
					$preprocessed_conversation[ $facet['field_key'] ],
					$search_term,
					$location
				);
			} else {
				$match = strpos(
					$preprocessed_conversation[ $facet['field_key'] ],
					$search_term
				) !== false;
			}

			if ( $match ) {
				$next_score += 1 << $facet['relevance'];
			}

			return $next_score;

		},
		0
	);

}

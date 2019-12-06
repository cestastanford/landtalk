<?php
/**
 * Defines REST endpoints for the API used by the front-end.
 *
 * @package Land Talk Custom Theme
 */

/*
*	Registers `/conversations` endpoint.
*/

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			REST_API_NAMESPACE,
			'/conversations',
			array(
				'methods'  => 'GET',
				'callback' => 'landtalk_get_conversations',
			)
		);
	}
);


/**
 * Adds REST endpoint for retrieving Conversations.  API description
 * of GET parameters:
 *
 * `query`     The set of results to apply filters, sorting and
 *             pagination to.  `query=all` returns all published
 *             Conversations, `query=featured` returns the Featured
 *             Conversations as set in Options, and `query=related`
 *             returns the Conversations related to the conversation
 *             referenced by ID in `relatedId`.  Default is `all`.
 *
 * `filterBy`  The filters to apply to the queried set.  `filterBy=relevance`
 *             performs a relevance search for the term in `relevanceSearchTerm`.
 *             Default is none.
 *
 * `orderBy`   Ordering to apply to the filtered queried set.
 *             `orderBy=rand` sorts randomly, `orderBy=RAND(seed)`
 *             sorts randomly using the provided seed, `orderBy=relevance`
 *             sorts by descending relevance score (only works
 *             if filtered by relevance), `orderBy=popular` sorts
 *             by descending page views, and `orderBy=recent` sorts
 *             by initial publication date from most recent to
 *             least recent.  Default is `rand` if not filtering
 *             by relevance; `relevance` if filtering by relevance.
 *             NOTE: if not sorting by random, sorts are stabilized
 *             by sorting by descending ID if primary keys are equal.
 *
 * `perPage`   If `perPage=all`, all results are returned.  Otherwise,
 *             up to `perPage` results are returned.  Default is `all`.
 *
 * `page`      If `perPage` is not `all`, this will return the
 *             specified page of results.  Default is `0`, the
 *             first page.
 *
 * `pad`       If `perPage` is not `all` and the number of results
 *             is less than `perPage`, setting `pad=rand` will
 *             pad add as many random results as required to create
 *             a complete page.  Default is none.
 *
 * `for`       If `for=mapOnly`, only the limited amount of information
 *             required for rendering points to the map will be
 *             returned.  Default is none.
 *
 * @param WP_REST_Request $request The WP REST request object.
 */
function landtalk_get_conversations( WP_REST_Request $request ) {

	// Basic WP query params for `query=all`.
	$args = array(
		'post_type'      => CONVERSATION_POST_TYPE,
		'posts_per_page' => -1,
	);

	// Applies default random sorting.
	if ( isset( $request['orderBy'] ) ) {
		if ( strncasecmp( $request['orderBy'], 'rand', 4 ) === 0 ) {
			$args['orderby'] = $request['orderBy'];
		}
	} else {
		$args['orderby'] = 'rand';
	}

	// Params for `featured` query.
	if ( 'featured' === $request['query'] ) {
		$args['post__in'] = get_field( 'featured_conversations', 'options' );
	}

	// Params for `related` query.
	if ( 'related' === $request['query'] ) {

		$keywords             = get_field( 'keywords', $request['relatedId'] );
		$args['post__not_in'] = array( $request['relatedId'] );
		$args['tax_query']    = array( // phpcs:ignore
			array(
				'taxonomy' => KEYWORDS_TAXONOMY,
				'field'    => 'term_id',
				'terms'    => isset( $keywords ) ? array_map(
					function( $term ) {
						return $term->term_id;
					},
					$keywords
				) : array(),
			),
		);

	}

	// Performs WP query to retrieve post objects.
	$query         = new WP_Query( $args );
	$conversations = $query->get_posts();

	// Applies `relevance` filter.
	if (
		isset( $request['filterBy'] ) &&
		strpos( $request['filterBy'], 'relevance' ) !== false
	) {

		$order_by_relevance = (
			! isset( $request['orderBy'] ) ||
			'relevance' === $request['orderBy']
		);

		$conversations = landtalk_filter_conversations_by_relevance(
			$conversations,
			$request['relevanceSearchTerm'],
			$order_by_relevance
		);

	}

	// Sorts by descending `popular`.
	if ( 'popular' === $request['orderBy'] ) {
		usort(
			$conversations,
			function( $a, $b ) {
				$a_view_count = get_field( 'view_count', $a );
				$b_view_count = get_field( 'view_count', $b );
				$difference   = $b_view_count - $a_view_count;
				if ( 0 === $difference ) {
					return $b->ID - $a->ID;
				} else {
					return $difference;
				}
			}
		);
	}

	// Sorts by descending `recent`.
	if ( 'recent' === $request['orderBy'] ) {
		usort(
			$conversations,
			function( $a, $b ) {
				$a_date     = get_the_date( 'U', $a );
				$b_date     = get_the_date( 'U', $b );
				$difference = $b_date - $a_date;
				if ( 0 === $difference ) {
					return $b->ID - $a->ID;
				} else {
					return $difference;
				}
			}
		);
	}

	// Paginates & pads with random.
	if ( isset( $request['perPage'] ) && 'all' !== $request['perPage'] ) {

		// Limits to indicated page of results.
		$per_page                = (int) $request['perPage'];
		$n_pages                 = ceil( count( $conversations ) / $per_page );
		$page                    = isset( $request['page'] ) ? (int) $request['page'] : 0;
		$offset                  = $page * $per_page;
		$conversations_in_page   = array_slice( $conversations, $offset, $per_page );
		$n_conversations_in_page = count( $conversations_in_page );

		// Pads with random if indicated.
		if (
			'rand' === $request['pad'] &&
			$n_conversations_in_page < $per_page
		) {

			$addl_conversations_query_args = array(
				'post_type'      => CONVERSATION_POST_TYPE,
				'posts_per_page' => $per_page - $n_conversations_in_page,
				'post__not_in'   => array_map( // Exclude queried Conversations.
					function( $conversation ) {
						return $conversation->ID;
					},
					$conversations
				),
				'orderby'        => 'rand',
			);

			// Exclude Conversation specified in `relatedId`.
			if ( isset( $request['relatedId'] ) ) {
				$addl_conversations_query_args['post__not_in'][] =
					$request['relatedId'];
			}

			$addl_conversations = ( new WP_Query(
				$addl_conversations_query_args
			) )->get_posts();

			$conversations_in_page = array_merge(
				$conversations_in_page,
				$addl_conversations
			);

		}
	} else {
		$n_pages               = 1;
		$conversations_in_page = $conversations;
	}

	// Prepares Conversations for JSON payload.
	$prepared_conversations = array();
	$for_map_only           = 'mapOnly' === $request['for'];
	foreach ( $conversations_in_page as $conversation ) {
		$prepared_conversations[] = landtalk_prepare_conversation_for_rest_response(
			$conversation,
			$for_map_only
		);
	}

	return array(
		'conversations' => $prepared_conversations,
		'nPages'        => $n_pages,
	);

}


/**
 * Retrieves the appropriate fields from a Conversation object
 * for a REST response.  Includes fields necessary for rendering
 * on the Conversation Map and as a Conversation Excerpt.
 *
 * @param WP_Post $conversation The Conversation post object.
 * @param bool    $for_map_only Whether this response is for the
 *                map, in which case fewer fields will be pulled
 *                and included.
 */
function landtalk_prepare_conversation_for_rest_response(
	$conversation,
	$for_map_only = false
) {

	$response               = array();
	$response['id']         = $conversation->ID;
	$response['link']       = get_permalink( $conversation );
	$response['place_name'] = get_field( 'place_name', $conversation );
	$response['location']   = get_field( 'location', $conversation )['lat_lng'];

	if ( ! $for_map_only ) {

		$historical_image_object = get_field(
			'historical_image',
			$conversation
		)['image_file'];

		if ( isset( $historical_image_object['sizes']['medium_large'] ) ) {

			$response['historical_image_url'] =
				$historical_image_object['sizes']['medium_large'];

		} else {
			$response['historical_image_url'] = $historical_image_object['url'];
		}

		$response['summary'] = get_field( 'summary', $conversation );

	}

	return $response;

}


/*
*	Registers `/lessons` endpoint.
*/

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			REST_API_NAMESPACE,
			'/lessons',
			array(
				'methods'  => 'GET',
				'callback' => 'landtalk_get_lessons',
			)
		);
	}
);


/**
 * Retrieves lessons.
 *
 * @param WP_REST_Request $request The WP REST request object.
 */
function landtalk_get_lessons( WP_REST_Request $request ) {

	$args = array( 'post_type' => LESSON_POST_TYPE );

	// Order the pages correctly.
	if ( isset( $request['orderBy'] ) ) {

		$args['orderby'] = $request['orderBy'];

	}

	// Retrieve the correct number of lessons per page.
	if ( isset( $request['perPage'] ) ) {

		$args['posts_per_page'] = $request['perPage'];

	} else {
		$args['posts_per_page'] = -1;
	}

	// Retrieve the corect page of lessons.
	if ( isset( $request['page'] ) && isset( $request['perPage'] ) ) {

		$args['offset'] = $request['page'] * $request['perPage'];

	}

	// Retrieve search term results.
	if ( isset( $request['searchTerm'] ) ) {

		$args['s'] = $request['searchTerm'];

	}

	$query   = new WP_Query( $args );
	$lessons = $query->get_posts();

	$prepared_lessons = array();
	foreach ( $lessons as $lesson ) {

		$prepared_lessons[] = landtalk_prepare_lesson_for_rest_response( $lesson );

	}

	return array(
		'lessons' => $prepared_lessons,
		'nPages'  => $query->max_num_pages,
	);

}


/**
 * Prepares a Lesson object for REST response.
 *
 * @param WP_Post $post The Lesson post object.
 */
function landtalk_prepare_lesson_for_rest_response( $post ) {

	$response                 = array();
	$response['id']           = $post->ID;
	$response['link']         = get_permalink( $post );
	$response['lesson_title'] = get_field( 'lesson_title', $post );
	$image_object             = get_field( 'image', $post );
	if ( isset( $image_object['sizes']['medium_large'] ) ) {

		$response['image_url'] = $image_object['sizes']['medium_large'];

	} else {
		$response['image_url'] = $image_object['url'];
	}

	$response['subject']   = get_field( 'subject', $post );
	$response['subject_2'] = get_field( 'subject_2', $post );
	$response['grade']     = get_field( 'grade', $post );
	$response['synopsis']  = get_field( 'synopsis', $post );
	return $response;

}


/*
*	Registers `/validate-youtube` endpoint.
*/

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			REST_API_NAMESPACE,
			'/validate-youtube',
			array(
				'methods'  => 'GET',
				'callback' => function( $request ) {
					$str = $request['str'];
					$id = landtalk_get_youtube_id( $str );
					return array(
						'str'   => $str,
						'id'    => $id,
						'valid' => landtalk_check_youtube_id_valid( $id ),
					);
				},
			)
		);
	}
);

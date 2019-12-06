<?php
/**
 * Page template for a single Conversation post.
 *
 * @package Land Talk Custom Theme
 */

get_header();
while ( have_posts() ) :
	the_post();

	// Retrieves images.
	$historical_image_object = get_field( 'historical_image' )['image_file'];
	if ( isset( $historical_image_object['sizes']['large'] ) ) {
		$historical_image = $historical_image_object['sizes']['large'];
	} else {
		$historical_image = $historical_image_object['url'];
	}

	$current_image_object = get_field( 'current_image' )['image_file'];
	if ( isset( $current_image_object['sizes']['large'] ) ) {
		$current_image = $current_image_object['sizes']['large'];
	} else {
		$current_image = $current_image_object['url'];
	}

	// Updates view count if user is not logged in.
	if ( ! is_user_logged_in() ) {

		$view_count = (int) get_field( 'view_count' );
		if ( empty( $view_count ) ) {
			$view_count = 1;
		} else {
			$view_count++;
		}

		update_field( 'view_count', $view_count );

	}

	$keywords = landtalk_get_keywords( $post );

	$keywords_with_links = array_map(
		function( $keyword ) {
			return array(
				'name' => $keyword,
				'link' => get_site_url() . '/conversations/#keyword=' . rawurlencode( $keyword ),
			);
		},
		$keywords
	);

	$conversations_link = get_site_url() . '/conversations/#selected-marker=' . get_the_ID();

	?>

<!-- Images -->
<div class="full-bleed-container">
	<div class="columns is-gapless">
		<div class="column is-half">
			<div class=" conversation-image" style="background-image: url('<?php echo esc_attr( $historical_image ); ?>');"></div>
		</div>
		<div class="column is-half">
			<div class=" conversation-image" style="background-image: url('<?php echo esc_attr( $current_image ); ?>');"></div>
		</div>
	</div>
</div>

<!-- Image Titles -->
<div class="container conversation-image-titles">
	<div class="columns is-centered">
		<div class="column is-6 has-text-left">
			<div class="has-text-weight-bold">
				<?php the_field( 'place_name' ); ?>,
				<?php echo esc_html( get_field( 'historical_image' )['year_taken'] ); ?>
			</div>
			<div class="conversation-image-description">
				<?php echo esc_html( get_field( 'historical_image' )['description'] ); ?>
			</div>
		</div>
		<div class="column is-2"></div>
		<div class="column is-6 has-text-right">
			<div class="has-text-weight-bold">
				<?php the_field( 'place_name' ); ?>,
				<?php echo esc_html( get_field( 'current_image' )['year_taken'] ); ?>
			</div>
			<div class="conversation-image-description">
				<?php echo esc_html( get_field( 'current_image' )['description'] ); ?>
			</div>
		</div>
	</div>
</div>

<!-- Post Title -->
<div class="container">
	<div class="columns level">
		<div class="column is-7 level-item is-size-2 has-text-weight-light">
			<?php the_field( 'place_name' ); ?>
		</div>
		<a href="<?php echo esc_attr( $conversations_link ); ?>" class="column is-5 level-item react-component mini-conversation-map" data-component-name="MiniConversationMap" data-component-props="<?php echo esc_attr( landtalk_encode_json_for_html_attr( array( 'postId' => get_the_ID() ) ) ); ?>"></a>
	</div>
</div>

<!-- Conversation Section -->
<div class="container collapsible-section">
	<div class="section-title is-size-4 has-text-weight-light">Conversation</div>
	<hr>
	<div class="columns">
		<div class="column is-two-thirds">
			<iframe width="100%" height="350" src="https://www.youtube.com/embed/<?php echo esc_attr( landtalk_get_youtube_id( get_field( 'youtube_url' )['url'] ) ); ?>?rel=0&amp;showinfo=0" frameborder="0" gesture="media" allow="encrypted-media" allowfullscreen></iframe>
		</div>
		<div class="column is-one-third">
			<div><?php the_field( 'summary' ); ?></div>
			<br>
			<div class="conversation-meta">
				<?php if ( get_field( 'observer_full_name' ) ) : ?>
					<strong>Observer: </strong><?php the_field( 'observer_full_name' ); ?>
				<?php endif; ?>
			</div>
			<div class="conversation-meta">
				<?php if ( get_field( 'interviewer_full_name' ) ) : ?>
					<strong>Interviewer: </strong><?php the_field( 'interviewer_full_name' ); ?>
				<?php endif; ?>
			</div>
			<div class="conversation-meta">
				<?php if ( get_field( 'grade_level__age' ) ) : ?>
					<strong>Grade Level/Age: </strong><?php the_field( 'grade_level__age' ); ?>
				<?php endif; ?>
			</div>
			<div class="conversation-meta">
				<?php if ( null !== get_field( 'date', false, false ) && strlen( get_field( 'date', false, false ) ) === 8 ) : ?>
					<strong>Interview Date: </strong><?php the_field( 'date' ); ?>
				<?php endif; ?>
			</div>
			<div class="conversation-meta"><strong>Submission Date: </strong><?php the_date(); ?></div>
			<div class="conversation-meta">
				<strong>Keywords: </strong>
				<?php foreach ( $keywords_with_links as $keyword ) : ?>
					<a class="conversation-keyword" href="<?php echo esc_url( $keyword['link'] ); ?>"><!--
					---><?php echo esc_html( $keyword['name'] ); ?><!--
				---></a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>

<!-- About This Place Section -->
<div class="container collapsible-section">
	<div class="section-title is-size-4 has-text-weight-light">About This Place</div>
	<hr>
	<div class="conversation-response">
		<strong>Historic Appearance</strong>
		<div><?php the_field( 'used_to_look' ); ?></div>
	</div>
	<div class="conversation-response">
		<strong>Changes over Time</strong>
		<div><?php the_field( 'has_changed' ); ?></div>
	</div>
	<div class="conversation-response">
		<strong>Historic & Current Activities</strong>
		<div><?php the_field( 'activities' ); ?></div>
	</div>
	<?php if ( get_field( 'additional_information' ) ) : ?>
		<div class="conversation-response">
			<strong>Additional Information</strong>
			<div><?php the_field( 'additional_information' ); ?></div>
		</div>
	<?php endif; ?>
</div>

<!-- Conversation Transcript Section -->
	<?php if ( get_field( 'transcript' ) ) : ?>
<div class="container collapsible-section">
	<div class="section-title is-size-4 has-text-weight-light">Conversation Transcript</div>
	<hr>
	<div class="content conversation-transcript"><?php the_field( 'transcript' ); ?></div>
</div>
	<?php endif; ?>

<!-- Share & Report -->
<div class="container">
	<div class="columns is-centered">
		<div class="column is-one-third">
			<div class="level">
				<div class="level-item">
					<div class="fb-share-button" data-href="<?php the_permalink(); ?>" data-layout="button" data-size="large" data-mobile-iframe="true"></div>
				</div>
				<div class="level-item">
					<a class="twitter-share-button" href="https://twitter.com/intent/tweet" data-size="large">Tweet</a>
				</div>
				<div class="level-item">
					<a href="mailto:?subject=Land%20Talk%20Conversation&body=<?php the_permalink(); ?>" class="email-share-link">share by email</a>
				</div>
			</div>
		</div>
	</div>
	<div class="columns is-centered">
		<div class="column is-two-thirds has-text-centered">
			<a href="../../report-conversation?conversation=<?php the_ID(); ?>" class="has-text-grey is-underlined">If this conversation violates Land Talk submission standards, click here to report it.</a>
		</div>
	</div>
</div>

<!-- You Might Also Like -->
<div class="container">
	<h3 class="is-size-5 has-text-weight-bold has-text-centered has-text-grey has-space-below">You Might Also Like</h3>
	<div class="react-component" data-component-name="RelatedConversations" data-component-props="<?php echo esc_attr( landtalk_encode_json_for_html_attr( array( 'postId' => get_the_ID() ) ) ); ?>"></div>
</div>

	<?php

endwhile;
get_footer();

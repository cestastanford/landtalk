<?php

/*
*   Registers the "caption" shortcode.
*/

function landtalk_caption_shortcode( $atts, $content = null ) {

    $content = wptexturize( $content );
    $content = do_shortcode( $content );
    return '<div class="caption">' . $content . '</div>';

}

add_shortcode( 'caption', 'landtalk_caption_shortcode' );


/*
*   Registers the "conversation_video" shortcode.
*/

function landtalk_conversation_video_shortcode( $atts ) {

    $a = shortcode_atts( array(
        'slug' => null,
    ), $atts );

    $conversation = get_page_by_path( $a['slug'], 'OBJECT', CONVERSATION_POST_TYPE );
    if ( empty( $conversation ) ) return;

    ob_start();

    ?>

<div class="conversation-video-shortcode">
    <iframe width="100%" height="600" src="https://www.youtube.com/embed/<?php echo landtalk_get_youtube_id( get_field( 'youtube_url', $conversation )['url'] ); ?>?rel=0&amp;showinfo=0" frameborder="0" gesture="media" allow="encrypted-media" allowfullscreen></iframe>
    <div class="caption">
        <strong><?php echo get_the_title( $conversation ); ?>: </strong>
        <span><?php echo get_field( 'summary', $conversation, false ); ?></span>
    </div>
</div>

    <?php

    return ob_get_clean();

}

add_shortcode( 'conversation_video', 'landtalk_conversation_video_shortcode' );


/*
*   Registers the "conversation_card" shortcode.  Supports inserting
*   multiple cards by comma-separating the slugs.
*/

function landtalk_conversation_cards_shortcode( $atts ) {

    $a = shortcode_atts( array(
        'slugs' => null,
    ), $atts );

    $slugs = preg_split( '/\s*,\s*/', $a['slugs'] );
    $conversations = array();
    foreach ( $slugs as $slug ) {
        $conversation = get_page_by_path( $slug, 'OBJECT', CONVERSATION_POST_TYPE );
        if ( ! empty ( $conversation ) ) {
            array_push( $conversations, $conversation );
        }
    }

    $json_props = landtalk_encode_json_for_html_attr( array(
        'conversations' => array_map( 'landtalk_prepare_conversation_for_rest_response', $conversations ),
    ) );

    ob_start();

    ?>

<div class="conversation-cards-shortcode">
    <div class="react-component" data-component-name="ExcerptGallery" data-component-props="<?php echo $json_props; ?>"></div>
</div>

    <?php

    return ob_get_clean();

}

add_shortcode( 'conversation_cards', 'landtalk_conversation_cards_shortcode' );

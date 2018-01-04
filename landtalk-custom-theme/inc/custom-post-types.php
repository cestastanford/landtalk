<?php

/*
*   Removes unused post type menu options.
*/

function landtalk_remove_unused_menu_options() {
    
    remove_menu_page( 'edit.php' ); // removes Posts
    remove_menu_page( 'edit-comments.php' ); // removes Comments

}

add_action( 'admin_menu', 'landtalk_remove_unused_menu_options' );


/*
*   Registers Conversation custom post type.
*/

function landtalk_register_conversation_post_type() {

    register_post_type( CONVERSATION_POST_TYPE, array(
        'labels' => array(
            'name' => 'Conversations',
            'singular_name' => 'Conversation',
            'add_new_item' => 'Add New Conversation',
            'edit_item' => 'Edit Conversation',
            'new_item' => 'New Conversation',
            'view_item' => 'View Conversation',
            'view_items' => 'View Conversations',
            'search_items' => 'Search Conversations',
            'not_found' => 'No Conversations Found',
            'not_found_in_trash' => 'No Conversations found in Trash',
            'all_items' => 'All Conversations',
            'archives' => 'Conversation Archives',
            'attributes' => 'Conversation Attributes',
            'insert_into_item' => 'Insert into Conversation',
            'uploaded_to_this_item' => 'Uploaded to this Conversation',
        ),
        'menu_icon' => 'dashicons-admin-site',
        'public' => true,
        'rewrite' => array( 'slug' => 'conversations' ),
        'show_in_rest' => true,
        'rest_base' => 'conversations',
        'supports' => array( 'title' ),
        'taxonomies' => array( KEYWORDS_TAXONOMY ),
    ) );

}

add_action( 'init', 'landtalk_register_conversation_post_type' );


/*
*   Includes ACF fields in REST responses for Conversations.
*/

function landtalk_add_custom_fields_to_rest( $data, $post, $request ) {  
    
    $_data = $data->data;
    $fields = get_fields( $post->ID );

    foreach ( $fields as $key => $value ) {
        $_data[ $key ] = $value;
    }

    $data->data = $_data;
    return $data;

}

add_filter( 'rest_prepare_' . CONVERSATION_POST_TYPE, 'landtalk_add_custom_fields_to_rest', 10, 3);


/*
*   Adds REST endpoint for retrieving the Featured Conversations.
*/

function landtalk_get_featured_conversations() {

    return get_field( 'featured_conversations', 'options' );

}

function landtalk_register_featured_conversations_endpoint() {
  
    register_rest_route( 'landtalk', '/conversations/featured', array(
        
        'methods' => 'GET',
        'callback' => 'landtalk_get_featured_conversations',

    ) );

}

add_action( 'rest_api_init', 'landtalk_register_featured_conversations_endpoint' );


/*
*   Registers Report custom post type.
*/

function landtalk_register_report_post_type() {

    register_post_type( REPORT_POST_TYPE, array(
        'labels' => array(
            'name' => 'Reports',
            'singular_name' => 'Report',
            'add_new_item' => 'Add New Report',
            'edit_item' => 'Edit Report',
            'new_item' => 'New Report',
            'view_item' => 'View Report',
            'view_items' => 'View Reports',
            'search_items' => 'Search Reports',
            'not_found' => 'No Reports Found',
            'not_found_in_trash' => 'No Reports found in Trash',
            'all_items' => 'All Reports',
            'archives' => 'Report Archives',
            'attributes' => 'Report Attributes',
            'insert_into_item' => 'Insert into Report',
            'uploaded_to_this_item' => 'Uploaded to this Report',
        ),
        'menu_icon' => 'dashicons-thumbs-down',
        'public' => true,
        'rewrite' => array( 'slug' => 'reports' ),
        'show_in_rest' => true,
        'rest_base' => 'reports',
        'supports' => array( 'title' ),
    ) );

}

add_action( 'init', 'landtalk_register_report_post_type' );

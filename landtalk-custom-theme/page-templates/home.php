<?php

/*
*   Template Name: Land Talk: Home
*/

get_header();

?>

<div class="container">
    <div class="columns is-centered">
        <div class="column is-10 home-page-intro-text">
            <?php the_field( 'intro_text' ); ?>
        </div>
    </div>
</div>
<div class="react-component" data-component-name="ConversationMap">React Component</div>
<div class="container">
    <h3 class="is-size-5 has-text-weight-bold has-text-centered has-text-grey has-space-below">Featured Conversations</h3>
    <div class="react-component" data-component-name="FeaturedConversations">React Component</div>
</div>
<div class="container">
    <div class="columns is-centered">
        <div class="column is-10 content">
            <?php the_field( 'body_text' ); ?>
        </div>
    </div>
</div>

<?php

get_footer();

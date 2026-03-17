<?php
function register_vgv_metis_meta() {
    $meta_fields = array(
        '_metis_text_type'   => 'string',
        '_metis_text_length' => 'number',
        '_post_count' => 'number'
    );
    
    foreach ( $meta_fields as $meta_key => $meta_type ) {
        $args = array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => $meta_type,
            'auth_callback' => function( $allowed, $meta_key, $post_id, $user_id, $cap, $caps ) {
                return current_user_can( 'edit_post', $post_id );
            },
        );

        // Register for 'page' post type
        register_post_meta( 'page', $meta_key, $args );

        // Register for 'post' post type
        register_post_meta( 'post', $meta_key, $args );
    }
}
add_action('init', 'register_vgv_metis_meta');
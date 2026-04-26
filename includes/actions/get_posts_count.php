<?php
use WP_VGWORT\Db_Pixels;
function get_posts_count() {
    // Check for nonce security
    vgw_metis_verify_metabox_nonce( 'security' );
    vgw_metis_get_authorized_post_id( 'post' );

    $post_data = wp_unslash( $_POST );

    if (isset($post_data['public_identification_id']) && is_scalar( $post_data['public_identification_id'] )) {
        $publicIdentificationId = sanitize_text_field( (string) $post_data['public_identification_id'] );
        if ($publicIdentificationId!=null) {
            wp_send_json_success(array(
                'posts_count' => Db_Pixels::get_assigned_posts_count($publicIdentificationId)
            ));
        }
    }
    wp_send_json_success(array('posts_count' => 0));
}
add_action('wp_ajax_get_posts_count', 'get_posts_count');

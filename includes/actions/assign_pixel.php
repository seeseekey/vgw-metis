<?php

use WP_VGWORT\Metabox;

function assign_pixel_to_post_ajax() {
    vgw_metis_verify_metabox_nonce( 'security' );
    $post_id   = vgw_metis_get_authorized_post_id( 'post' );
    $post_data = wp_unslash( $_POST );
    $text_type = isset( $post_data['wp_metis_metabox_text_type'] ) && is_scalar( $post_data['wp_metis_metabox_text_type'] ) ? sanitize_key( (string) $post_data['wp_metis_metabox_text_type'] ) : '';

    $plugin = vgw_metis_get_instance();

    $metaBox = new Metabox( $plugin );
    $response = $metaBox->automatic_assign_pixel_action( $post_id );
    $metaBox->set_post_metadata( $post_id, $text_type );
    wp_send_json_success( $response );
}
add_action('wp_ajax_assign_pixel_to_post', 'assign_pixel_to_post_ajax');

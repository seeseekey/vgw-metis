<?php

use WP_VGWORT\Metabox;

function manually_assign_pixel_to_post_ajax() {
    vgw_metis_verify_metabox_nonce( 'nonce' );
    $post_id                  = vgw_metis_get_authorized_post_id( 'post' );
    $post_data                = wp_unslash( $_POST );
    $public_identification_id = isset( $post_data['public_identification_id'] ) && is_scalar( $post_data['public_identification_id'] ) ? sanitize_text_field( (string) $post_data['public_identification_id'] ) : '';

    $plugin = vgw_metis_get_instance();

    $metaBox = new Metabox( $plugin );
    $response = $metaBox->manual_assign_pixel_action( $post_id, $public_identification_id );

    if ( $response['assigned'] ) {
        wp_send_json_success( $response );
    }

    wp_send_json_error( $response );
}
add_action('wp_ajax_manually_assign_pixel_to_post', 'manually_assign_pixel_to_post_ajax');

<?php
use WP_VGWORT\Metabox;

function remove_pixel_from_post_ajax() {
    vgw_metis_verify_metabox_nonce( 'security' );
    $post_id = vgw_metis_get_authorized_post_id( 'post' );

    $plugin = vgw_metis_get_instance();
    $metaBox = new Metabox( $plugin );
    $response = $metaBox->remove_pixel_action( $post_id );

    wp_send_json_success( $response );
}
add_action('wp_ajax_remove_pixel_from_post', 'remove_pixel_from_post_ajax');

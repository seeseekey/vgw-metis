<?php
use WP_VGWORT\Assignment_Services;

function remove_pixel_from_post_ajax() {
    // Check for nonce security
    vgw_metis_verify_metabox_nonce( 'security' );

    // Ensure the post ID is received
    $post_id = vgw_metis_get_authorized_post_id( 'post' );
        
    // Call the function to assign the pixel
    $result = Assignment_Services::unassign_pixel_from_post($post_id);
    if( $result === 0 ) {
        wp_send_json_success(array('message' => 'Keine Pixel wurden aus dem Beitrag entfernt.'));
    }
    else if ( $result === true || $result === 1) {
        wp_send_json_success(array('message' => 'Pixel wurde erfolgreich aus dem Beitrag entfernt.'));
    }
    else {
        wp_send_json_error(array('message' => 'Unbekannter Fehler.'));
    }
}
add_action('wp_ajax_remove_pixel_from_post', 'remove_pixel_from_post_ajax');

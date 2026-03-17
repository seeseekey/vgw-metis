<?php
use WP_VGWORT\Assignment_Services;

function remove_pixel_from_post_ajax() {
    // Check for nonce security
    if (!check_ajax_referer('wp_metis_metabox_nonce', 'security', false)) {
        wp_send_json_error(array('message' => 'Nonce-Überprüfung fehlgeschlagen.'));
        wp_die();  // Terminate execution if nonce fails
    }

    // Ensure the post ID is received
    if (isset($_POST['post_id'])) {
        $post_id = intval($_POST['post_id']);
        
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
    } else {
        wp_send_json_error(array('message' => 'Ungültige Beitrags-ID.'));
    }
}
add_action('wp_ajax_remove_pixel_from_post', 'remove_pixel_from_post_ajax');
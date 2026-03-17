<?php
use WP_VGWORT\Db_Pixels;
function get_posts_count() {

    // Check for nonce security
    if (!check_ajax_referer('wp_metis_metabox_nonce', 'security', false)) {
        wp_send_json_error(array('message' => 'Nonce-Überprüfung fehlgeschlagen.'));
        wp_die();  // Terminate execution if nonce fails
    }

    if (isset($_POST['public_identification_id'])) {
        $publicIdentificationId = sanitize_text_field($_POST['public_identification_id']);
        if ($publicIdentificationId!=null) {
            wp_send_json_success(array(
                'posts_count' => Db_Pixels::get_assigned_posts_count($publicIdentificationId)
            ));
        }
    }
    wp_send_json_success(array('posts_count' => 0));
}
add_action('wp_ajax_get_posts_count', 'get_posts_count');
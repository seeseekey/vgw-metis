<?php
use WP_VGWORT\Metabox;

function metabox_check_validity_and_ownership() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( [ 'message' => esc_html__( 'Permission denied', 'vgw-metis' ) ], 400 );
    }

    $plugin = vgw_metis_get_instance();
    $metaBox = new Metabox( $plugin );
    $metaBox->is_valid_and_ownership_check();
}
add_action('wp_ajax_check_validity_and_ownership', 'metabox_check_validity_and_ownership');
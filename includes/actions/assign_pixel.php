<?php

use WP_VGWORT\Db_Pixels;
use WP_VGWORT\Metabox;
use WP_VGWORT\Services;

function assign_pixel_to_post_ajax() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( [ 'message' => esc_html__('Permission denied', 'vgw-metis') ], 403 );
    }

    $plugin = vgw_metis_get_instance();

    $post_id = (int) $_POST['post_id'];

    $metaBox = new Metabox( $plugin );
    $metaBox->automatic_assign_pixel_action();
    $metaBox->set_post_metadata();
}
add_action('wp_ajax_assign_pixel_to_post', 'assign_pixel_to_post_ajax');
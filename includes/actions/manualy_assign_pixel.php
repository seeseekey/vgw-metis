<?php

use WP_VGWORT\Common;
use WP_VGWORT\Db_Pixels;
use WP_VGWORT\Metabox;
use WP_VGWORT\Services;

function manually_assign_pixel_to_post_ajax() {
    $plugin = vgw_metis_get_instance();

    $post_id = (int) $_POST['post_id'];

    // set text type accordingly
    //Services::set_text_type( $post_id );
    // set text length accordingly
    Services::set_text_length( $post_id );

    $pixel               = Db_Pixels::get_pixel_by_post_id( $post_id );
    if (is_object($pixel)) {
        $public_identification_id = $pixel->public_identification_id;
        $private_identification_id = $pixel->private_identification_id; // assuming it's available
    } else {
        $public_identification_id = '';
        $private_identification_id = '';
    }

    $metaBox = new Metabox( $plugin );
    $metaBox->manual_assign_pixel_action();
    //$metaBox->set_post_metadata();
}
add_action('wp_ajax_manually_assign_pixel_to_post', 'manually_assign_pixel_to_post_ajax');
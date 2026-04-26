<?php
use WP_VGWORT\Metabox;

function metabox_check_validity_and_ownership() {
    $plugin = vgw_metis_get_instance();
    $metaBox = new Metabox( $plugin );
    $metaBox->is_valid_and_ownership_check();
}
add_action('wp_ajax_check_validity_and_ownership', 'metabox_check_validity_and_ownership');

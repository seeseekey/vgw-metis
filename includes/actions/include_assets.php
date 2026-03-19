<?php
function vgw_metis_sidebar_enqueue_assets() {
    if ( is_admin() ) {
        wp_enqueue_style(
            'vgw-metis-sidebar-style',
            plugin_dir_url( __FILE__ ) . '../../admin/css/vgw-metis-sidebar.css', // Adjust the path to your CSS file
            array(),
            '1.0'
        );
    }
}
add_action('enqueue_block_assets', 'vgw_metis_sidebar_enqueue_assets');
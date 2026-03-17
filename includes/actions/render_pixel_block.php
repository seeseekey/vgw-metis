<?php
use WP_VGWORT\Services;

function render_pixel_block_for_post( $content ) {
    if ( is_singular( 'post' ) || is_singular( 'page' ) ) {
        global $post;

        /**
         * Allow the services object to be filtered for testing purposes.
         * Developers can replace the Services instance with a mock or alternative implementation.
         */

        $pixelData = Services::get_pixel_for_post( $post->ID );

        $pixel_block = '';

        if ( $pixelData && $pixelData->public_identification_id ) {
            $pixel_block  = '<!-- VG WORT Tracking code START -->';
            $pixel_block .= '<img id="metis-img-pixel" src="https://vg08.met.vgwort.de/na/' . esc_attr( $pixelData->public_identification_id ) . '" width="1" height="1" alt="" />';
            $pixel_block .= '<!-- VG WORT Tracking code END -->';
        }

        $content = $content . $pixel_block;
    }
    return $content;
}
add_filter( 'the_content', 'render_pixel_block_for_post' );
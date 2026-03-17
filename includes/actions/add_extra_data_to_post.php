<?php

use WP_VGWORT\Services;

add_filter( 'rest_prepare_post', 'add_extra_data_to_rest_response', 10, 3 );
// Add data to the REST response for pages
add_filter( 'rest_prepare_page', 'add_extra_data_to_rest_response', 10, 3 );

function add_extra_data_to_rest_response( $response, $post, $request ) {
    
    $pixel = Services::get_pixel_for_post($post->ID);

    // If a result is found, return it as the meta value
    if ($pixel) {
        $response->data['public_identification_id'] = $pixel->get_public_identification_id();
        $response->data['private_identification_id'] = $pixel->get_private_identification_id();
    }
    
    return $response;
}
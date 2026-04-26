<?php

/**
 * Send a generic invalid request response for AJAX security failures.
 *
 * @return void
 */
function vgw_metis_send_invalid_request(): void {
    wp_send_json_error( [ 'message' => esc_html__( 'Invalid request.', 'vgw-metis' ) ], 400 );
}

/**
 * Send a generic permission denied response for AJAX authorization failures.
 *
 * @return void
 */
function vgw_metis_send_permission_denied(): void {
    wp_send_json_error( [ 'message' => esc_html__( 'Permission denied.', 'vgw-metis' ) ], 403 );
}

/**
 * Verify the shared METIS metabox nonce from a request parameter.
 *
 * @param string $query_arg Request parameter that contains the nonce.
 *
 * @return void
 */
function vgw_metis_verify_metabox_nonce( string $query_arg ): void {
    if ( ! check_ajax_referer( 'wp_metis_metabox_nonce', $query_arg, false ) ) {
        vgw_metis_send_invalid_request();
    }
}

/**
 * Read and validate a post ID from an AJAX request.
 *
 * @param string $source Use 'post' for $_POST or 'request' for $_REQUEST.
 *
 * @return int
 */
function vgw_metis_get_request_post_id( string $source = 'request' ): int {
    $request = $source === 'post' ? wp_unslash( $_POST ) : wp_unslash( $_REQUEST );
    $post_id = 0;

    if ( isset( $request['post_id'] ) && is_scalar( $request['post_id'] ) ) {
        $post_id = absint( $request['post_id'] );
    }

    if ( ! $post_id ) {
        vgw_metis_send_invalid_request();
    }

    return $post_id;
}

/**
 * Read a post ID and assert that the current user can edit that post.
 *
 * @param string $source Use 'post' for $_POST or 'request' for $_REQUEST.
 *
 * @return int
 */
function vgw_metis_get_authorized_post_id( string $source = 'request' ): int {
    $post_id = vgw_metis_get_request_post_id( $source );

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        vgw_metis_send_permission_denied();
    }

    return $post_id;
}

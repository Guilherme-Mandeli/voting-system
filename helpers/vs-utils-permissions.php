<?php
defined( 'ABSPATH' ) || exit;

/**
 * Get nonce from $_POST (supports multiple field names).
 *
 * @param array $fields Possible field names.
 * @return string
 */
function vs_get_post_nonce( $fields = array( 'nonce', '_ajax_nonce' ) ) {
    foreach ( $fields as $field ) {
        if ( isset( $_POST[ $field ] ) ) {
            return sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
        }
    }
    return '';
}

/**
 * Verify a nonce from $_POST.
 *
 * @param string $action The nonce action.
 * @param array $fields Possible field names.
 * @return bool
 */
function vs_verify_post_nonce( $action, $fields = array( 'nonce', '_ajax_nonce' ) ) {
    $nonce = vs_get_post_nonce( $fields );
    return wp_verify_nonce( $nonce, $action );
}

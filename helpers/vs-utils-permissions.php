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

/**
 * Create and return a nonce for a specific action.
 *
 * @param string $action The nonce action.
 * @return string
 */
function vs_create_nonce( $action ) {
    return wp_create_nonce( $action );
}

/**
 * Create a nonce URL for admin actions.
 *
 * @param string $url Base URL.
 * @param string $action Nonce action.
 * @return string
 */
function vs_create_nonce_url( $url, $action ) {
    return wp_nonce_url( $url, $action );
}

/**
 * Create a nonce field for forms.
 *
 * @param string $action Nonce action.
 * @param string $name Field name (optional).
 * @param bool $referer Include referer field.
 * @param bool $echo Echo or return.
 * @return string
 */
function vs_create_nonce_field( $action, $name = '_wpnonce', $referer = true, $echo = true ) {
    return wp_nonce_field( $action, $name, $referer, $echo );
}

/**
 * Verify nonce and die with error if invalid (for AJAX).
 *
 * @param string $action Nonce action.
 * @param string $field_name Field name to check.
 * @param string $error_message Custom error message.
 */
function vs_verify_ajax_nonce_or_die( $action, $field_name = 'nonce', $error_message = 'Nonce inválido.' ) {
    if ( ! isset( $_POST[ $field_name ] ) || ! wp_verify_nonce( $_POST[ $field_name ], $action ) ) {
        wp_send_json_error( $error_message );
    }
}

/**
 * Verify nonce and die with error if invalid (for form submissions).
 *
 * @param string $action Nonce action.
 * @param string $field_name Field name to check.
 * @param string $error_message Custom error message.
 */
function vs_verify_form_nonce_or_die( $action, $field_name = '_wpnonce', $error_message = 'Ação não autorizada.' ) {
    if ( ! isset( $_POST[ $field_name ] ) || ! wp_verify_nonce( $_POST[ $field_name ], $action ) ) {
        wp_die( $error_message, 'Erro', ['response' => 403] );
    }
}

/**
 * Generate a unique token for specific user and voting combination.
 *
 * @param int $user_id User ID.
 * @param int $votacao_id Voting ID.
 * @param string $prefix Token prefix.
 * @return string
 */
function vs_generate_user_token( $user_id, $votacao_id, $prefix = 'vs_obrigado' ) {
    return wp_create_nonce( $prefix . '_' . $user_id . '_' . $votacao_id );
}

/**
 * Verify a user-specific token.
 *
 * @param string $token Token to verify.
 * @param int $user_id User ID.
 * @param int $votacao_id Voting ID.
 * @param string $prefix Token prefix.
 * @return bool
 */
function vs_verify_user_token( $token, $user_id, $votacao_id, $prefix = 'vs_obrigado' ) {
    return wp_verify_nonce( $token, $prefix . '_' . $user_id . '_' . $votacao_id );
}

/**
 * Create nonce for post-specific actions (restore, trash, etc.).
 *
 * @param string $action Base action name.
 * @param int $post_id Post ID.
 * @return string
 */
function vs_create_post_action_nonce( $action, $post_id ) {
    return wp_create_nonce( $action . '_' . $post_id );
}

/**
 * Create nonce URL for post-specific actions.
 *
 * @param string $base_url Base URL.
 * @param string $action Action name.
 * @param int $post_id Post ID.
 * @param array $extra_params Additional URL parameters.
 * @return string
 */
function vs_create_post_action_url( $base_url, $action, $post_id, $extra_params = [] ) {
    $params = array_merge( [
        'action' => $action,
        'post_id' => $post_id
    ], $extra_params );
    
    $url = add_query_arg( $params, $base_url );
    return wp_nonce_url( $url, $action . '_' . $post_id );
}

/**
 * Constants for common nonce actions used throughout the plugin.
 */
class VS_Nonce_Actions {
    const AJAX_GENERAL = 'vs_ajax_nonce';
    const AJAX_RANKING = 'vs_ranking_nonce';
    const AJAX_UNIFICACAO = 'vs_unificacao_nonce';
    const FORM_VOTING = 'vs_votacao_nonce';
    const FORM_QUESTIONS = 'vs_salvar_perguntas';
    const FORM_INFO = 'vs_info_metabox_nonce';
    const FORM_ANSWER_DETAILS = 'vs_answer_details_nonce';
    const ACTION_RESTORE = 'vs_restore_response';
    const ACTION_TRASH = 'vs_trash_response';
    const TOKEN_THANK_YOU = 'vs_obrigado';
}

/**
 * Helper function to get all nonce actions as array.
 *
 * @return array
 */
function vs_get_nonce_actions() {
    return [
        'ajax_general' => VS_Nonce_Actions::AJAX_GENERAL,
        'ajax_ranking' => VS_Nonce_Actions::AJAX_RANKING,
        'ajax_unificacao' => VS_Nonce_Actions::AJAX_UNIFICACAO,
        'form_voting' => VS_Nonce_Actions::FORM_VOTING,
        'form_questions' => VS_Nonce_Actions::FORM_QUESTIONS,
        'form_info' => VS_Nonce_Actions::FORM_INFO,
        'form_answer_details' => VS_Nonce_Actions::FORM_ANSWER_DETAILS,
        'action_restore' => VS_Nonce_Actions::ACTION_RESTORE,
        'action_trash' => VS_Nonce_Actions::ACTION_TRASH,
        'token_thank_you' => VS_Nonce_Actions::TOKEN_THANK_YOU,
    ];
}

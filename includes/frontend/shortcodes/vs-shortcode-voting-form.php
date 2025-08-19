<?php
// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sistema de Votação com Modo Leitura e Edição - Shortcode [votacao_formulario]
 */

 
/**
 * Shortcode principal para exibir o sistema de votação.
 * Alterna entre modo leitura e modo edição conforme estado do voto e permissão.
 */
function vs_votacao_shortcode($atts) {
    global $post;

    if (!$post || $post->post_type !== 'votacoes') {
        return '<p><em>Este shortcode só pode ser usado em uma votação.</em></p>';
    }

    $votacao_id = $post->ID;
    
    // Debug: Adicionar log para verificar o estado
    error_log('[DEBUG] vs_votacao_shortcode - Votação ID: ' . $votacao_id);
    vs_debug_voting_questions($votacao_id);

    $data_fim = get_post_meta($votacao_id, '_vs_data_fim', true);
    $status_atual = get_post_meta($votacao_id, '_vs_status', true);
    $questions = vs_get_voting_questions($votacao_id); // Usa função helper
    $permitir_edicao = get_post_meta($votacao_id, 'vs_permitir_edicao', true);

    if (!is_user_logged_in()) {
        return '<p>Você precisa estar logado para votar.</p><p><a href="' . wp_login_url(get_permalink()) . '" class="vs-login-link">Entrar</a></p>';
    }

    $user_id = get_current_user_id();
    $ja_votou = vs_user_already_voted($votacao_id, $user_id); // Usa função helper
    
    // Usa função helper para obter respostas do usuário
    $respostas = vs_get_user_responses($user_id, $votacao_id);

    // Adicionar validação mais robusta
    $questions = vs_get_voting_questions($votacao_id);
    $validation_errors = vs_validate_voting_questions($questions);
    
    if (!empty($validation_errors)) {
        error_log('[ERROR] Erros de validação encontrados: ' . implode(', ', $validation_errors));
        return '<div class="vs-error"><strong>Erro na configuração da votação:</strong><ul><li>' . implode('</li><li>', $validation_errors) . '</li></ul><p><em>Entre em contato com o administrador.</em></p></div>';
    }

    ob_start();
    
    // Garante que o CSS seja carregado usando a classe centralizada
    VS_CSS_Conditional_Loader::ensure_css_for_shortcode('votacao_formulario');
    
    include VS_PLUGIN_PATH . 'templates/public/template-voting-form.php';
    return ob_get_clean();
}
add_shortcode('votacao_formulario', 'vs_votacao_shortcode');

/**
 * Hook adicional para garantir que o CSS seja carregado no footer se necessário
 */
add_action('wp_footer', function() {
    // Se o shortcode foi usado mas o CSS inline não foi incluído, inclui agora
    if (did_action('vs_voting_form_shortcode_used') && !did_action('vs_voting_form_css_included')) {
        echo VS_CSS_Conditional_Loader::output_inline_css_for_shortcode('votacao_formulario');
        do_action('vs_voting_form_css_included');
    }
}, 999);

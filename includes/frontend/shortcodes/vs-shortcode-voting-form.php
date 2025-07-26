<?php
// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sistema de Votação com Modo Leitura e Edição - Shortcode [votacao_formulario]
 */

/**
 * Renderiza o formulário de votação com perguntas dinâmicas e respostas pré-preenchidas.
 */
function vs_render_formulario_votacao($perguntas, $votacao_id, $respostas = []) {
    ob_start();
    include VS_PLUGIN_PATH . 'templates/public/template-voting-form-fields.php';
    return ob_get_clean();
}

/**
 * Renderiza as respostas do usuário em modo leitura, em tabela organizada.
 */
function vs_render_respostas_votacao($perguntas, $respostas) {
    ob_start();
    include VS_PLUGIN_PATH . 'templates/public/template-voting-responses.php';
    return ob_get_clean();
}

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

    $data_fim = get_post_meta($votacao_id, '_vs_data_fim', true);
    $status_atual = get_post_meta($votacao_id, '_vs_status', true);
    $perguntas = vs_get_voting_questions($votacao_id); // Usa função helper
    $permitir_edicao = get_post_meta($votacao_id, 'vs_permitir_edicao', true);

    if (!is_user_logged_in()) {
        return '<p>Você precisa estar logado para votar.</p><p><a href="' . wp_login_url(get_permalink()) . '">Entrar</a></p>';
    }

    $user_id = get_current_user_id();
    $ja_votou = vs_user_already_voted($votacao_id, $user_id); // Usa função helper
    
    // Usa função helper para obter respostas do usuário
    $respostas = vs_get_user_responses($user_id, $votacao_id);

    ob_start();
    include VS_PLUGIN_PATH . 'templates/public/template-voting-form.php';
    return ob_get_clean();
}
add_shortcode('votacao_formulario', 'vs_votacao_shortcode');

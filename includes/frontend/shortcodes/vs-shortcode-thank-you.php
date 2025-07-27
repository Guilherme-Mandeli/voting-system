<?php
/**
 * Shortcode: [votacao_obrigado]
 *
 * Exibe a tela de confirmação da votação ("Obrigado por votar").
 */

if (!defined('ABSPATH')) {
    exit;
}

function vs_shortcode_votacao_obrigado() {
    // Garante que o CSS seja carregado usando a classe centralizada
    VS_CSS_Conditional_Loader::ensure_css_for_shortcode('votacao_obrigado');
    
    if (!is_user_logged_in()) {
        return vs_gera_404();
    }

    $user_id = get_current_user_id();

    // Coleta parâmetros da URL
    $votacao_id = isset($_GET['votacao_id']) ? intval($_GET['votacao_id']) : 0;
    $token      = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

    if (!$votacao_id || !$token) {
        return vs_gera_404();
    }

    // Validação do token usando função helper
    if (!vs_validate_thank_you_token($user_id, $votacao_id, $token)) {
        wp_redirect(home_url('/votacao'));
        exit; // Finaliza a execução para garantir que o redirecionamento aconteça
    }

    // Busca as respostas do usuário usando função helper
    $posts_resposta = vs_get_existing_response($user_id, $votacao_id);

    if (empty($posts_resposta)) {
        return '<p><em>Não encontramos suas respostas. Pode ser que você não tenha respondido a esta votação.</em></p>';
    }

    $post_resposta_id = $posts_resposta[0];

    // Recupera as respostas formatadas usando função helper
    $respostas = vs_get_formatted_responses($post_resposta_id);

    // Remove o token após o uso único (proteção extra)
    $transient_key = 'vs_obrigado_token_' . $user_id . '_' . $votacao_id;
    delete_transient($transient_key);

    // Recupera as perguntas da votação usando função helper
    $perguntas = vs_get_voting_questions($votacao_id);

    // Carrega o template e retorna o conteúdo
    ob_start();
    include VS_PLUGIN_PATH . 'templates/public/template-thank-you.php';
    return ob_get_clean();
}

add_shortcode('votacao_obrigado', 'vs_shortcode_votacao_obrigado');

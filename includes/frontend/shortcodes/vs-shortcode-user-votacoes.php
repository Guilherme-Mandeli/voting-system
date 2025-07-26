<?php
/**
 * Shortcodes para página /votacoes - Área do usuário
 *
 * Shortcodes disponíveis:
 * - [votacoes_usuario_ativas] - Votações que o usuário respondeu e ainda podem ser editadas
 * - [votacoes_usuario_encerradas] - Votações que o usuário participou e já estão encerradas
 * - [votacoes_disponiveis] - Votações em aberto que o usuário ainda não participou
 *
 * @package VotingSystem\Frontend\Shortcodes
 */

defined('ABSPATH') || exit;

/**
 * Shortcode: [votacoes_usuario_ativas]
 * 
 * Lista todos os votos que o usuário já respondeu e ainda podem ser editados.
 * Para cada item, exibe:
 * - Título da votação
 * - Data
 * - Resumo das respostas
 * - Botão "Editar Voto"
 */
function vs_shortcode_votacoes_usuario_ativas($atts) {
    // Verifica se o usuário está logado
    if (!is_user_logged_in()) {
        return '<div class="vs-login-required"><p>Faça login para visualizar suas votações.</p></div>';
    }

    $user_id = get_current_user_id();
    
    // Busca todas as respostas do usuário
    $args_respostas = [
        'post_type' => 'votacao_resposta',
        'author' => $user_id,
        'post_status' => ['publish', 'private'],
        'posts_per_page' => -1,
        'meta_key' => 'vs_votacao_id',
    ];
    
    $respostas_posts = get_posts($args_respostas);
    $votacoes_ativas = [];
    
    foreach ($respostas_posts as $resposta_post) {
        $votacao_id = get_post_meta($resposta_post->ID, 'vs_votacao_id', true);
        $votacao = get_post($votacao_id);
        
        if (!$votacao || $votacao->post_status !== 'publish') {
            continue;
        }
        
        $status = get_post_meta($votacao_id, '_vs_status', true);
        $data_fim = get_post_meta($votacao_id, '_vs_data_fim', true);
        
        // Verifica se a votação ainda está ativa (não encerrada)
        $is_encerrada = ($status === 'encerrada') || vs_check_votacao_status($data_fim);
        
        if (!$is_encerrada) {
            // Obtém as respostas detalhadas
            $respostas_detalhadas = get_post_meta($resposta_post->ID, 'vs_respostas_detalhadas', true);
            $perguntas = get_post_meta($votacao_id, 'vs_perguntas', true);
            
            $resumo_respostas = vs_generate_response_summary($respostas_detalhadas, $perguntas);
            
            $votacoes_ativas[] = [
                'id' => $votacao_id,
                'titulo' => $votacao->post_title,
                'data' => get_the_date('d/m/Y', $votacao),
                'link' => get_permalink($votacao_id),
                'resumo_respostas' => $resumo_respostas,
                'data_resposta' => get_post_meta($resposta_post->ID, 'vs_data_envio', true),
            ];
        }
    }
    
    // Renderiza o template
    ob_start();
    include(plugin_dir_path(dirname(dirname(dirname(__FILE__)))) . 'templates/public/template-votacoes-usuario-ativas.php');
    return ob_get_clean();
}

/**
 * Shortcode: [votacoes_usuario_encerradas]
 * 
 * Lista todas as votações que o usuário participou e que já estão encerradas.
 * Para cada item, exibe:
 * - Título da votação
 * - Status: Encerrada
 * - Resumo das respostas
 * - (Opcional) Botão "Ver Respostas"
 */
function vs_shortcode_votacoes_usuario_encerradas($atts) {
    // Verifica se o usuário está logado
    if (!is_user_logged_in()) {
        return '<div class="vs-login-required"><p>Faça login para visualizar suas votações.</p></div>';
    }

    $user_id = get_current_user_id();
    
    // Busca todas as respostas do usuário
    $args_respostas = [
        'post_type' => 'votacao_resposta',
        'author' => $user_id,
        'post_status' => ['publish', 'private'],
        'posts_per_page' => -1,
        'meta_key' => 'vs_votacao_id',
    ];
    
    $respostas_posts = get_posts($args_respostas);
    $votacoes_encerradas = [];
    
    foreach ($respostas_posts as $resposta_post) {
        $votacao_id = get_post_meta($resposta_post->ID, 'vs_votacao_id', true);
        $votacao = get_post($votacao_id);
        
        if (!$votacao || $votacao->post_status !== 'publish') {
            continue;
        }
        
        $status = get_post_meta($votacao_id, '_vs_status', true);
        $data_fim = get_post_meta($votacao_id, '_vs_data_fim', true);
        
        // Verifica se a votação está encerrada
        $is_encerrada = ($status === 'encerrada') || vs_check_votacao_status($data_fim);
        
        if ($is_encerrada) {
            // Obtém as respostas detalhadas
            $respostas_detalhadas = get_post_meta($resposta_post->ID, 'vs_respostas_detalhadas', true);
            $perguntas = get_post_meta($votacao_id, 'vs_perguntas', true);
            
            $resumo_respostas = vs_generate_response_summary($respostas_detalhadas, $perguntas);
            
            $votacoes_encerradas[] = [
                'id' => $votacao_id,
                'titulo' => $votacao->post_title,
                'data' => get_the_date('d/m/Y', $votacao),
                'link' => get_permalink($votacao_id),
                'resumo_respostas' => $resumo_respostas,
                'data_resposta' => get_post_meta($resposta_post->ID, 'vs_data_envio', true),
                'status' => 'Encerrada',
            ];
        }
    }
    
    // Renderiza o template
    ob_start();
    include(plugin_dir_path(dirname(dirname(dirname(__FILE__)))) . 'templates/public/template-votacoes-usuario-encerradas.php');
    return ob_get_clean();
}

/**
 * Shortcode: [votacoes_disponiveis]
 * 
 * Lista todas as votações em aberto nas quais o usuário ainda não participou.
 * Para cada item, exibe:
 * - Título da votação
 * - Prazo
 * - Breve descrição
 * - Botão "Participar"
 */
function vs_shortcode_votacoes_disponiveis($atts) {
    // Verifica se o usuário está logado
    if (!is_user_logged_in()) {
        return '<div class="vs-login-required"><p>Faça login para visualizar suas votações.</p></div>';
    }

    $user_id = get_current_user_id();
    
    // Busca todas as votações publicadas
    $args_votacoes = [
        'post_type' => 'votacoes',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_vs_status',
                'value' => 'aberta',
                'compare' => '=',
            ]
        ],
    ];
    
    $votacoes_query = new WP_Query($args_votacoes);
    $votacoes_disponiveis = [];
    
    if ($votacoes_query->have_posts()) {
        while ($votacoes_query->have_posts()) {
            $votacoes_query->the_post();
            $votacao_id = get_the_ID();
            
            // Verifica se o usuário já votou nesta votação
            if (!vs_user_already_voted($votacao_id, $user_id)) {
                // Verifica se a votação não está encerrada por data
                $data_fim = get_post_meta($votacao_id, '_vs_data_fim', true);
                $is_encerrada = vs_check_votacao_status($data_fim);
                
                if (!$is_encerrada) {
                    $prazo_texto = '';
                    if ($data_fim) {
                        $prazo_texto = 'Prazo: ' . date('d/m/Y', strtotime($data_fim));
                    }
                    
                    $votacoes_disponiveis[] = [
                        'id' => $votacao_id,
                        'titulo' => get_the_title(),
                        'descricao' => get_the_excerpt() ?: wp_trim_words(get_the_content(), 20),
                        'link' => get_permalink(),
                        'prazo' => $prazo_texto,
                        'data_fim' => $data_fim,
                    ];
                }
            }
        }
        wp_reset_postdata();
    }
    
    // Renderiza o template
    ob_start();
    include(plugin_dir_path(dirname(dirname(dirname(__FILE__)))) . 'templates/public/template-votacoes-disponiveis.php');
    return ob_get_clean();
}

/**
 * Função helper para gerar resumo das respostas
 */
function vs_generate_response_summary($respostas_detalhadas, $perguntas) {
    if (!is_array($respostas_detalhadas) || !is_array($perguntas)) {
        return 'Respostas não disponíveis';
    }
    
    // Se as respostas estão no formato ['respostas' => [...]]
    $respostas = isset($respostas_detalhadas['respostas']) ? $respostas_detalhadas['respostas'] : $respostas_detalhadas;
    
    $resumo = [];
    $max_items = 3; // Máximo de itens no resumo
    $count = 0;
    
    foreach ($respostas as $index => $resposta) {
        if ($count >= $max_items) break;
        
        if (isset($perguntas[$index])) {
            $pergunta_label = $perguntas[$index]['label'] ?? 'Pergunta ' . ($index + 1);
            $resposta_texto = is_array($resposta) ? implode(', ', $resposta) : $resposta;
            
            // Limita o tamanho da resposta
            if (strlen($resposta_texto) > 50) {
                $resposta_texto = substr($resposta_texto, 0, 50) . '...';
            }
            
            $resumo[] = '<strong>' . esc_html($pergunta_label) . ':</strong> ' . esc_html($resposta_texto);
            $count++;
        }
    }
    
    if (count($respostas) > $max_items) {
        $resumo[] = '<em>... e mais ' . (count($respostas) - $max_items) . ' resposta(s)</em>';
    }
    
    return implode('<br>', $resumo);
}

// Registra os shortcodes
add_shortcode('votacoes_usuario_ativas', 'vs_shortcode_votacoes_usuario_ativas');
add_shortcode('votacoes_usuario_encerradas', 'vs_shortcode_votacoes_usuario_encerradas');
add_shortcode('votacoes_disponiveis', 'vs_shortcode_votacoes_disponiveis');
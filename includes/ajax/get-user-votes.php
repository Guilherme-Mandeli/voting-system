<?php
defined( 'ABSPATH' ) || exit;

/**
 *
 * ------------------------------------------------------------
 * $respostas (array)
 * ------------------------------------------------------------
 * Estrutura salva no post type 'votacao_resposta' na meta 'vs_respostas_detalhadas'
 *
 * @var array $respostas Exemplo:
 * [
 *     0 => 'Minha resposta', // Resposta da primeira pergunta (índice 0)
 *     1 => 'B',              // Resposta da segunda pergunta (índice 1)
 *     // ... outros índices conforme a ordem das perguntas
 * ]
 *
 * - O índice de cada resposta corresponde à ordem da pergunta definida no array $questions.
 * - O valor pode ser uma string (resposta única) ou array (ex: múltiplas opções de um checkbox).
 *
 *
 * ------------------------------------------------------------
 * $questions (array)
 * ------------------------------------------------------------
 * Estrutura salva no post_meta com a chave 'vs_questions'
 * Definida via metabox no editor do post tipo 'votacoes'
 *
 * @var array $questions Exemplo:
 * [
 *     0 => [
 *         'label' => 'Enunciado 1',        // Texto da pergunta
 *         'tipo' => 'texto',               // Tipo do campo: texto, select, radio, checkbox, etc.
 *         'options' => [                    // Lista de opções (usado em tipos select, radio, checkbox)
 *             0 => ''                      // Pode ser vazio em perguntas do tipo texto
 *         ],
 *         'obrigatoria' => 1               // Booleano (1 ou 0) indicando se a pergunta é obrigatória
 *     ],
 *     1 => [
 *         'label' => 'Enunciado 2',
 *         'tipo' => 'select',
 *         'options' => [
 *             0 => 'A',
 *             1 => 'B',
 *             2 => 'C',
 *         ],
 *         'obrigatoria' => 1
 *     ],
 *     // ... outras perguntas
 * ]
 *
 * - O índice de cada item reflete a ordem de exibição das perguntas.
 * - A chave 'options' só é usada para campos que apresentam múltiplas opções (select, radio, checkbox).
 * - 'obrigatoria' define se o campo deve ser validado no momento do envio.
 */

// Função de tratamento do Ajax
function vs_ajax_get_user_votes() {
    vs_verify_ajax_nonce_or_die( VS_Nonce_Actions::AJAX_GENERAL, 'vs_nonce' );
    
    // Verifica permissões do usuário
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permissão insuficiente.' );
    }

    // Pega os parâmetros enviados via POST
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $votacao_id = isset($_POST['votacao_id']) ? intval($_POST['votacao_id']) : 0;

    // Verifica se os parâmetros são válidos
    if (!$user_id || !$votacao_id) {
        wp_send_json_error('Parâmetros inválidos.');
    }

    // Obtém os dados do usuário
    $user = get_userdata($user_id);
    if (!$user) {
        wp_send_json_error('Usuário não encontrado.');
    }

    // Verifica se a votação existe
    $post_votacao = get_post($votacao_id);
    if (!$post_votacao) {
        wp_send_json_error('Votação não encontrada.');
    }

    // Recupera o post do tipo 'votacao_resposta' para este usuário e votação
    $args = [
        'post_type' => 'votacao_resposta',
        'author' => $user_id,
        'meta_query' => [
            [
                'key' => 'vs_votacao_id',
                'value' => $votacao_id,
                'compare' => '=',
            ],
        ],
        'posts_per_page' => 1,
        'post_status' => ['publish', 'private'],
        'fields' => 'ids',
    ];
    $posts_resposta = get_posts($args);

    if (empty($posts_resposta)) {
        wp_send_json_error('Nenhuma resposta encontrada para este usuário nesta votação.');
    }

    $post_resposta_id = $posts_resposta[0];

    // Recupera as respostas detalhadas armazenadas na meta do post resposta
    $respostas = get_post_meta($post_resposta_id, 'vs_respostas_detalhadas', true);
    if (!$respostas || !is_array($respostas)) {
        wp_send_json_error('Nenhuma resposta encontrada para este usuário nesta votação.');
    }

    // Recupera as perguntas da votação
    $questions = get_post_meta($votacao_id, 'vs_questions', true);
    if (!$questions || !is_array($questions)) {
        wp_send_json_error('Nenhuma pergunta encontrada para esta votação.');
    }

    // Data da resposta do post meta (fallback para data do post)
    $data_resposta = get_post_meta($post_resposta_id, 'vs_data_envio', true);
    if (!$data_resposta) {
        $data_resposta = get_the_date('d/m/Y H:i', $post_resposta_id);
    }

    // Criação de array para formatar as respostas
    $respostas_formatadas = vs_format_user_answers( $respostas, $questions );
    
    // Retorna as respostas formatadas em formato JSON
    wp_send_json_success([
        'user_name' => $user->display_name,
        'data_envio' => $data_resposta, // Nome da chave para coincidir com o JavaScript
        'respostas' => $respostas_formatadas,
    ]);
}
add_action('wp_ajax_vs_get_user_votes', 'vs_ajax_get_user_votes');
add_action('wp_ajax_nopriv_vs_get_user_votes', 'vs_ajax_get_user_votes');

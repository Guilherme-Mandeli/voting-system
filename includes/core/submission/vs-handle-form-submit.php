<?php
// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 
 * Este arquivo gerencia a lógica central das votações do sistema,
 * incluindo o processamento do envio das respostas dos usuários,
 * controle de participação, geração de tokens de acesso à página de
 * agradecimento e a automação do encerramento das votações baseado
 * na data final definida no custom field '_vs_data_fim'.
 * 
 * Funcionalidades principais:
 * 
 * 1. Processa o envio da votação via formulário:
 *    - Valida usuário logado e nonce de segurança.
 *    - Salva as respostas individuais no user_meta.
 *    - Marca o usuário como participante na lista de votantes do post.
 *    - Gera um token temporário para acesso seguro à página de obrigado.
 *    - Redireciona para a página de confirmação/obrigado.
 * 
 * 2. Controla o status da votação baseado na data final (_vs_data_fim):
 *    - Ao salvar ou atualizar um post do tipo 'votacao', verifica a data final.
 *    - Se a data final for futura, agenda uma tarefa cron para encerrar
 *      a votação automaticamente à meia-noite do dia final.
 *    - Se a data final já passou, encerra a votação imediatamente.
 *    - Garante que agendamentos antigos sejam limpos para evitar duplicidade.
 * 
 * Hooks utilizados:
 * - admin_post_vs_enviar_voto: para processar votos enviados por usuários logados.
 * - admin_post_nopriv_vs_enviar_voto: bloqueia usuários não logados (mesma função).
 * - save_post: para agendar ou executar o encerramento automático.
 * - vs_encerrar_votacao_evento: evento customizado para finalizar a votação via cron.
 * 
 * Importante:
 * - O encerramento da votação é feito atualizando o meta '_vs_status' para 'encerrado'.
 * - O horário padrão para encerrar é 00:00 do dia definido em '_vs_data_fim'.
 * - O agendamento é único e atualizado a cada edição para evitar agendamentos duplicados.
 * 
 */

/**
 * Processa o envio da votação
 */
function vs_handle_form_submit() {
    if ( !is_user_logged_in() ) {
        wp_die('Você precisa estar logado para votar.', 'Erro', ['response' => 403]);
    }

    vs_verify_form_nonce_or_die( VS_Nonce_Actions::FORM_VOTING, 'vs_votacao_nonce' );
    
    $user_id = get_current_user_id();
    $votacao_id = intval($_POST['vs_votacao_id']);
    $respostas = $_POST['respostas'] ?? [];

    // Verifica se já existe post 'votacao_resposta' para este usuário e votação
    $existing_posts = vs_get_existing_response($user_id, $votacao_id);

    // Prepara post data para criação/atualização
    $post_data = [
        'post_type' => 'votacao_resposta',
        'post_status' => 'publish',
        'post_author' => $user_id,
        'post_title' => 'Resposta votação #' . $votacao_id . ' - Usuário #' . $user_id,
    ];

    if (!empty($existing_posts)) {
        // Atualiza o post existente
        $post_id = $existing_posts[0];
        $post_data['ID'] = $post_id;
        wp_update_post($post_data);
    } else {
        // Cria um novo post de resposta
        $post_id = wp_insert_post($post_data);
        if (is_wp_error($post_id) || $post_id === 0) {
            wp_die('Erro ao salvar a resposta da votação.', 'Erro', ['response' => 500]);
        }
    }

    // Atualiza os metadados do post de resposta
    vs_update_response_metadata($post_id, $votacao_id, $user_id, $respostas);

    // Marca o usuário como já votou e atualiza contagem
    vs_update_votantes($votacao_id, $user_id);

    // Gera e salva o token de agradecimento
    $token = vs_generate_thank_you_token($user_id, $votacao_id);

    // Redireciona para a tela de obrigado
    $redirect_url = home_url('/votacao/obrigado/?votacao_id=' . $votacao_id . '&token=' . $token);
    wp_safe_redirect($redirect_url);
    exit;
}
add_action('admin_post_vs_enviar_voto', 'vs_handle_form_submit');
add_action('admin_post_nopriv_vs_enviar_voto', 'vs_handle_form_submit');

/**
 * Função que encerra a votação (usada pelo cron)
 */
function vs_encerrar_votacao_callback($post_id) {
    update_post_meta($post_id, '_vs_status', 'encerrado');
}
add_action('vs_encerrar_votacao_evento', 'vs_encerrar_votacao_callback', 10, 1);

/**
 * Agenda (ou executa imediatamente) o encerramento da votação ao salvar ou editar.
 */
function vs_agenda_encerramento_votacao($post_id, $post, $update) {
    // Evita loops ou execuções indevidas
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
    if ($post->post_type !== 'votacao') return;

    // Recupera a data de fim
    $data_fim = get_post_meta($post_id, '_vs_data_fim', true);
    if (!$data_fim) return; // Nenhuma data de fim definida

    // Monta timestamp para 00:00 da data final
    $timestamp_fim = strtotime($data_fim . ' 00:00:00');

    // Limpa agendamento antigo (se existir)
    wp_clear_scheduled_hook('vs_encerrar_votacao_evento', [$post_id]);

    // Verifica se a votação já deve ser encerrada
    if (vs_check_votacao_status($data_fim)) {
        update_post_meta($post_id, '_vs_status', 'encerrado');
    } else {
        // Agenda o encerramento para 00:00 do dia final
        wp_schedule_single_event($timestamp_fim, 'vs_encerrar_votacao_evento', [$post_id]);
    }
}
add_action('save_post', 'vs_agenda_encerramento_votacao', 10, 3);

<?php
/**
 * Metabox de preguntas de votación
 * 
 * @package VotingSystem\Metaboxes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registra el metabox de preguntas
 */
function vs_register_metabox_questions() {
    add_meta_box(
        'vs_metabox_perguntas',
        'Perguntas da Votação',
        'vs_render_metabox_questions',
        'votacoes',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'vs_register_metabox_questions');

/**
 * Renderiza el metabox de preguntas
 *
 * @param WP_Post $post Objeto del post actual
 */
function vs_render_metabox_questions($post) {
    // Incluye la vista del metabox
    require_once VS_PLUGIN_PATH . 'metaboxes/view-metabox-questions.php';
    vs_render_metabox_questions_view($post);
}

/**
 * Guarda los datos del metabox al guardar el post
 *
 * @param int $post_id ID del post siendo guardado
 */
function vs_save_metabox_questions($post_id) {
    // Verificar nonce e permissões - CORRIGIDO
    if (!isset($_POST['vs_nonce_questions']) || !wp_verify_nonce($_POST['vs_nonce_questions'], 'vs_salvar_perguntas')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Verificar se existem dados salvos anteriormente
    $existing_questions = get_post_meta($post_id, 'vs_questions', true);
    $has_existing_data = !empty($existing_questions) && is_array($existing_questions);

    // Salvar flag de permitir edição
    $permitir_edicao = isset($_POST['vs_permitir_edicao']) ? 1 : 0;
    update_post_meta($post_id, 'vs_permitir_edicao', $permitir_edicao);

    // Processar perguntas
    $questions = [];
    $temp_questions = [];
    
    // Log do POST completo para debug
    error_log('=== DEBUG SALVAMENTO PERGUNTAS - POST ID: ' . $post_id . ' ===');
    error_log('POST vs_questions: ' . print_r($_POST['vs_questions'] ?? [], true));
    error_log('Has existing data: ' . ($has_existing_data ? 'SIM' : 'NÃO'));

    if (isset($_POST['vs_questions']) && is_array($_POST['vs_questions'])) {
        foreach ($_POST['vs_questions'] as $index => $question_data) {
            $label = sanitize_text_field($question_data['label'] ?? '');
            $tipo = sanitize_text_field($question_data['tipo'] ?? 'texto');
            $options = array_map('sanitize_text_field', $question_data['options'] ?? []);
            $valores_reais = array_map('sanitize_text_field', $question_data['valores_reais'] ?? []);
            $obrigatoria = isset($question_data['obrigatoria']) ? true : false;
            $unificada = sanitize_text_field($question_data['unificada'] ?? '');
            $imported_vote_id = intval($question_data['imported_vote_id'] ?? 0);

            // Filtrar opções vazias antes de processar
            $filtered_options = [];
            $filtered_valores_reais = [];
            
            foreach ($options as $option_index => $option) {
                // Só manter opções que não estejam vazias (após trim)
                if (!empty(trim($option))) {
                    $filtered_options[] = $option;
                    // Manter o valor real correspondente se existir
                    $filtered_valores_reais[] = $valores_reais[$option_index] ?? $option;
                }
            }
            
            // Usar as opções filtradas
            $options = $filtered_options;
            $valores_reais = $filtered_valores_reais;

            // Log específico para cada pergunta
            error_log("=== PERGUNTA $index ===");
            error_log("  Label: '$label'");
            error_log("  Tipo: '$tipo'");
            error_log("  Options RAW: " . print_r($question_data['options'] ?? [], true));
            error_log("  Options FILTERED: " . print_r($options, true));
            error_log("  Valores Reais RAW: " . print_r($question_data['valores_reais'] ?? [], true));
            error_log("  Valores Reais FILTERED: " . print_r($valores_reais, true));
            error_log("  Options COUNT: " . count($options));
            error_log("  Valores Reais COUNT: " . count($valores_reais));

            // Processar imported_answers
            $imported_answers = wp_json_encode(['questions' => []]);
            if (isset($question_data['imported_answers']) && !empty($question_data['imported_answers'])) {
                $imported_answers = $question_data['imported_answers'];
                
                // Remover escapes se necessário e validar JSON
                $clean_json = stripslashes($imported_answers);
                $decoded = json_decode($clean_json, true);
                
                if ($decoded === null) {
                    // Tentar sem stripslashes também
                    $decoded = json_decode($imported_answers, true);
                    if ($decoded === null) {
                        $imported_answers = wp_json_encode(['questions' => []]);
                        error_log("  Imported Answers: JSON INVÁLIDO - usando fallback");
                    } else {
                        $imported_answers = $imported_answers; // Usar original
                        error_log("  Imported Answers: JSON válido (sem stripslashes)");
                    }
                } else {
                    $imported_answers = $clean_json; // Usar versão limpa
                    error_log("  Imported Answers: JSON válido (com stripslashes)");
                }
            } else {
                // Só aplicar fallback vs_get_imported_vote_data se for a primeira vez (não há dados salvos)
                if ($tipo === 'imported_vote' && $imported_vote_id > 0 && !$has_existing_data) {
                    $imported_answers = vs_get_imported_vote_data($imported_vote_id, $index);
                    error_log("  Imported Answers: Usando fallback vs_get_imported_vote_data (primeira vez)");
                } else {
                    // Se já existem dados salvos, preservar apenas as opções do formulário atual
                    $imported_answers = wp_json_encode(['questions' => []]);
                    if ($has_existing_data) {
                        error_log("  Imported Answers: Usando fallback vazio (dados já existem - preservando apenas opções do formulário)");
                    } else {
                        error_log("  Imported Answers: Usando fallback vazio (não é imported_vote)");
                    }
                }
            }

            // Solo agregar si tiene label - PRESERVANDO O ÍNDICE ORIGINAL
            if (!empty($label)) {
                $temp_questions[$index] = [
                    'label' => $label,
                    'tipo' => $tipo,
                    'options' => $options,
                    'valores_reais' => $valores_reais,
                    'obrigatoria' => $obrigatoria,
                    'unificada' => $unificada,
                    'imported_vote_id' => $imported_vote_id,
                    'imported_answers' => $imported_answers
                ];
                
                // Log da pergunta final salva
                error_log("  PERGUNTA $index SERÁ SALVA:");
                error_log("    Options finais: " . print_r($temp_questions[$index]['options'], true));
                error_log("    Valores reais finais: " . print_r($temp_questions[$index]['valores_reais'], true));
            } else {
                error_log("  PERGUNTA $index IGNORADA (label vazio)");
            }
        }
        
        // Reordenar sequencialmente apenas no final
        ksort($temp_questions); // Ordenar por chave
        $questions = array_values($temp_questions); // Reindexar sequencialmente
        
        // Log final do que será salvo
        error_log('=== QUESTIONS FINAIS A SEREM SALVAS ===');
        foreach ($questions as $final_index => $final_question) {
            error_log("Question $final_index:");
            error_log("  Label: " . $final_question['label']);
            error_log("  Tipo: " . $final_question['tipo']);
            error_log("  Options: " . print_r($final_question['options'], true));
            error_log("  Valores Reais: " . print_r($final_question['valores_reais'], true));
        }
    }

    // Obter dados anteriores para comparação
    $previous_questions = get_post_meta($post_id, 'vs_questions', true);
    error_log('=== DADOS ANTERIORES ===');
    error_log('Previous questions: ' . print_r($previous_questions, true));

    update_post_meta($post_id, 'vs_questions', $questions);
    
    // Verificar se foi salvo corretamente
    $saved_questions = get_post_meta($post_id, 'vs_questions', true);
    error_log('=== DADOS SALVOS (VERIFICAÇÃO) ===');
    error_log('Saved questions: ' . print_r($saved_questions, true));
    
    error_log('=== FIM DEBUG SALVAMENTO ===');
}
add_action('save_post', 'vs_save_metabox_questions');

/**
 * Callback AJAX para obtener template de pregunta
 */
function vs_ajax_get_pergunta_template() {
    $index = intval($_GET['index'] ?? 0);
    $question = [
        'obrigatoria' => true,
        'tipo' => 'texto' // Definir tipo padrão explicitamente
    ];
    
    // Incluir el template
    include VS_PLUGIN_PATH . 'templates/admin/template-metabox-question-row.php';
    
    wp_die();
}
add_action('wp_ajax_vs_get_pergunta_template', 'vs_ajax_get_pergunta_template');

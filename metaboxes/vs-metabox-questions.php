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
    // Verificar nonce e permissões
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

            $filtered_options = [];
            $filtered_valores_reais = [];
            $manual_items = [];
            $imported_items = [];

            // Decodificar imported_answers para obter manual_items e imported_items
            $imported_answers_decoded = [];
            
            if (!empty($question_data['imported_answers'])) {
                $imported_answers_decoded = json_decode($question_data['imported_answers'], true);
                if ($imported_answers_decoded) {
                    $manual_items = $imported_answers_decoded['manual_items'] ?? [];
                    $imported_items = $imported_answers_decoded['imported_items'] ?? [];
                }
            }
            
            $index_mapping = [];
            
            foreach ($options as $option_index => $option) {
                // Só manter opções que não estejam vazias (após trim)
                if (!empty(trim($option))) {
                    $new_index = count($filtered_options);
                    $index_mapping[$option_index] = $new_index;
                    $filtered_options[] = $option;
                    
                    // Para valores reais, verificar se existe um valor real
                    $real_value = $valores_reais[$option_index] ?? null;
                    
                    // Se tem valor real, adicionar ao array filtrado
                    if ($real_value !== null) {
                        $filtered_valores_reais[$new_index] = $real_value;
                    }
                }
            }
            
            // Reajustar os arrays manual_items e imported_items com os novos índices
            $new_manual_items = [];
            $new_imported_items = [];
            
            foreach ($manual_items as $old_index) {
                if (isset($index_mapping[$old_index])) {
                    $new_manual_items[] = $index_mapping[$old_index];
                }
            }
            
            foreach ($imported_items as $old_index) {
                if (isset($index_mapping[$old_index])) {
                    $new_imported_items[] = $index_mapping[$old_index];
                }
            }
            
            $manual_items = $new_manual_items;
            $imported_items = $new_imported_items;

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
            $imported_answers_data = ['questions' => [], 'manual_items' => $manual_items, 'imported_items' => $imported_items];
            
            if (isset($question_data['imported_answers']) && !empty($question_data['imported_answers'])) {
                $existing_imported_answers = $question_data['imported_answers'];
                
                // Remover escapes se necessário e validar JSON
                $clean_json = stripslashes($existing_imported_answers);
                $decoded = json_decode($clean_json, true);
                
                if ($decoded === null) {
                    // Tentar sem stripslashes também
                    $decoded = json_decode($existing_imported_answers, true);
                    if ($decoded === null) {
                        // JSON inválido - usar apenas os novos dados
                        $imported_answers = wp_json_encode($imported_answers_data);
                        error_log("  Imported Answers: JSON INVÁLIDO - usando fallback com manual/imported items");
                    } else {
                        // Mesclar dados existentes com novos arrays
                        $decoded['manual_items'] = $manual_items;
                        $decoded['imported_items'] = $imported_items;
                        $imported_answers = wp_json_encode($decoded);
                        error_log("  Imported Answers: JSON válido (sem stripslashes) - adicionando manual/imported items");
                    }
                } else {
                    // Mesclar dados existentes com novos arrays
                    $decoded['manual_items'] = $manual_items;
                    $decoded['imported_items'] = $imported_items;
                    $imported_answers = wp_json_encode($decoded);
                    error_log("  Imported Answers: JSON válido (com stripslashes) - adicionando manual/imported items");
                }
            } else {
                // Só aplicar fallback vs_get_imported_vote_data se for a primeira vez (não há dados salvos)
                if ($tipo === 'imported_vote' && $imported_vote_id > 0 && !$has_existing_data) {
                    $fallback_data = vs_get_imported_vote_data($imported_vote_id, $index);
                    $fallback_decoded = json_decode($fallback_data, true);
                    if ($fallback_decoded) {
                        $fallback_decoded['manual_items'] = $manual_items;
                        $fallback_decoded['imported_items'] = $imported_items;
                        $imported_answers = wp_json_encode($fallback_decoded);
                    } else {
                        $imported_answers = wp_json_encode($imported_answers_data);
                    }
                    error_log("  Imported Answers: Usando fallback vs_get_imported_vote_data com manual/imported items");
                } else {
                    // Usar apenas os novos dados
                    $imported_answers = wp_json_encode($imported_answers_data);
                    if ($has_existing_data) {
                        error_log("  Imported Answers: Usando dados com manual/imported items (dados já existem)");
                    } else {
                        error_log("  Imported Answers: Usando dados com manual/imported items (não é imported_vote)");
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

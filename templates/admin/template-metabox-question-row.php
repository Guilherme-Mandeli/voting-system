<?php
/**
 * Template para una fila de pregunta en el metabox
 * 
 * Variables disponibles:
 * - $index: Índice de la pregunta
 * - $question: Array con datos de la pregunta
 * 
 * @package VotingSystem\Templates\Admin
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="vs-pergunta">
    <div class="vs-question-index" style="float: right;">
        <span>#<?php echo esc_html($index + 1); ?></span>
    </div>
    <label>Pergunta:</label><br>
    <input
        type="text"
        name="vs_questions[<?php echo esc_attr($index); ?>][label]"
        value="<?php echo esc_attr($question['label'] ?? ''); ?>"
        style="width: 100%; margin: 5px 0 10px 0;"
        placeholder="Digite sua pergunta aqui..."
    >

    <label>Tipo de Resposta:</label><br>

    <select
        name="vs_questions[<?php echo esc_attr($index); ?>][tipo]"
        class="vs-tipo-campo"
        style="display: block; min-width: 250px; margin: 5px 0 10px 0;"
    >
        <?php
        $tipos = [
            'text' => 'Texto Livre', 
            'textarea' => 'Área de Texto',
            'number' => 'Número',
            'email' => 'Email',
            'date' => 'Data',
            'select' => 'Select', 
            'radio' => 'Radio', 
            'checkbox' => 'Checkbox',
            'imported_vote' => 'Importar respostas de uma votação...'
        ];
        
        // Garantir que sempre há um tipo definido
        $tipo_atual = $question['tipo'] ?? 'text';
        
        foreach ($tipos as $val => $label) {
            $selected = ($tipo_atual === $val) ? 'selected' : '';
            echo "<option value='{$val}' {$selected}>{$label}</option>";
        }
        ?>
    </select>
    
    <!-- Campo oculto para armazenar respostas importadas em JSON -->
    <input type="hidden"
        name="vs_questions[<?php echo esc_attr($index); ?>][imported_answers]"
        class="vs-imported-answers"
        vote-id-list="<?php 
            // Verifica se existem respostas importadas
            $imported_answers = $question['imported_answers'] ?? json_encode(['questions' => []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $answers_data = json_decode($imported_answers, true);
            // Validação e sanitização do ID
            $vote_id = isset($answers_data['questions'][0]['question_source']) ? 
                absint($answers_data['questions'][0]['question_source']) : 
                (isset($question['imported_vote_id']) ? absint($question['imported_vote_id']) : '');
            echo esc_attr($vote_id);
        ?>"
        value="<?php echo esc_attr($question['imported_answers'] ?? json_encode(['questions' => []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>">

    <!-- Botão para abrir modal (visível apenas quando tipo 'imported_vote' estiver selecionado) -->
    <div class="vs-votacao-anterior-container" style="display: none; margin-top: 10px;">
        <button type="button" class="button vs-selecionar-votacao">Abrir Votações</button>
        <div class="vs-votacao-selecionada"></div>

<!-- Modal de Seleção de Votação -->
<div id="vs-modal-votacao-anterior-<?php echo esc_attr($index); ?>" class="vs-modal" style="display: none;">
    <div class="vs-modal-content">
        <div class="vs-modal-header">
            <h2>Importar respostas de uma votação anterior</h2>
            <span class="vs-modal-close">&times;</span>
        </div>
        
        <div class="vs-modal-body">
            <div class="vs-modal-filters" style="margin-bottom: 15px; padding-bottom: 15px;">
                <div class="vs-filter-row" style="margin-bottom: 15px;">
                    <label>Ano:</label>
                    <select class="vs-filtro-ano" id="vs-filtro-ano-<?php echo esc_attr($index); ?>">
                        <option value="">Todos</option>
                        <?php
                        $anos = vs_get_votacoes_years();
                        foreach ($anos as $ano) {
                            echo "<option value='{$ano}'>{$ano}</option>";
                        }
                        ?>
                    </select>

                    <label>Evento:</label>
                    <select class="vs-filtro-evento" id="vs-filtro-evento-<?php echo esc_attr($index); ?>">
                        <option value="">Todos</option>
                        <?php
                        $eventos = get_terms(['taxonomy' => 'eventos', 'hide_empty' => false]);
                        foreach ($eventos as $evento) {
                            echo "<option value='{$evento->slug}'>{$evento->name}</option>";
                        }
                        ?>
                    </select>

                    <label>Status:</label>
                    <select class="vs-filtro-status" id="vs-filtro-status-<?php echo esc_attr($index); ?>">
                        <option value="">Todos</option>
                        <option value="encerrada">Encerrada</option>
                        <option value="aberta">Aberta</option>
                        <option value="em_pausa">Em Pausa</option>
                    </select>
                </div>

                <div class="vs-filter-row">
                    <div style="display: flex; gap: 5px;">
                        <input type="text" class="vs-busca-votacao" id="vs-busca-votacao-<?php echo esc_attr($index); ?>" placeholder="Buscar por código ou título..." style="flex: 1;">
                        <button type="button" class="button vs-buscar-votacao" style="padding: 0 10px;">
                            <span class="dashicons dashicons-search"></span>
                        </button>
                    </div>
                </div>
            </div>

            <div id="vs-votings-list" class="vs-modal-list">
                <!-- Lista de votações será carregada via AJAX -->
            </div>

            <div id="vs-alerta-status" class="vs-alert" style="display: none;">
                <p>Atenção: A votação selecionada ainda está em andamento. Os dados importados refletem o estado atual e não incluirão os novos votos enviados após este momento.</p>
                <div class="vs-alert-actions">
                    <button type="button" class="button vs-voltar">Voltar</button>
                    <button type="button" class="button button-primary vs-continuar">Continuar mesmo assim</button>
                </div>
            </div>
        </div>
    </div>
</div>
    </div>

    <!-- Campo oculto para valor unificada -->
    <input
        type="hidden"
        name="vs_questions[<?php echo esc_attr($index); ?>][unificada]"
        value="<?php echo esc_attr($question['unificada'] ?? ''); ?>"
    >

    <!-- Container de opções (para select, radio, checkbox e imported_vote) -->
    <div class="vs-options-container" style="display: <?php echo in_array($question['tipo'] ?? '', ['radio', 'checkbox', 'select', 'imported_vote']) ? 'block' : 'none'; ?>;">        
        <label>Opções:</label><br>
        <div class="vs-options">
            <?php
            // Só criar opção vazia se não houver opções existentes E não for tipo imported_vote
            $options = $question['options'] ?? [];
            $tipo_atual = $question['tipo'] ?? 'texto';
            
            // Filtrar opções vazias
            $options = array_filter($options, function($option) {
                return !empty(trim($option));
            });
            
            // Só adicionar opção vazia se não houver opções e não for imported_vote
            if (empty($options) && $tipo_atual !== 'imported_vote') {
                $options = [''];
            }
            
            foreach ($options as $option_index => $option) {
                // Verificar se existe um valor real para este índice específico
                $valor_real = $question['valores_reais'][$option_index] ?? null;
                
                // Decodificar imported_answers para verificar se é importada
                $imported_answers_data = json_decode($question['imported_answers'] ?? '{}', true);
                $imported_items = $imported_answers_data['imported_items'] ?? [];
                
                // Verificar se é importada usando nova estrutura de objetos
                $is_imported = false;
                $vote_id = null;
                $answer_question_index = null;
                
                if (is_array($imported_items)) {
                    foreach ($imported_items as $item) {
                        if (is_array($item)) {
                            // Nova estrutura: objetos com text e vs_valor_real
                            if (isset($item['text']) && $item['text'] === $option && 
                                isset($item['vs_valor_real']) && $item['vs_valor_real'] === $valor_real) {
                                $is_imported = true;
                                $vote_id = $item['vote_id'] ?? null;
                                $answer_question_index = $item['question_index'] ?? null;
                                break;
                            }
                        } else {
                            // Compatibilidade com estrutura antiga: índices numéricos
                            if ($item === $option_index) {
                                $is_imported = true;
                                break;
                            }
                        }
                    }
                }
                
                // Se não está nos arrays, usar lógica de fallback
                if (!$is_imported) {
                    $manual_items = $imported_answers_data['manual_items'] ?? [];
                    if (!in_array($option_index, $manual_items)) {
                        // Fallback: verificar se tem valor real diferente da opção
                        $is_imported = ($valor_real !== null && $valor_real !== $option);
                    }
                }
                
                $option_class = $is_imported ? 'vs-option-item imported_question' : 'vs-option-item';
                
                // Preparar atributos de metadados para opções importadas
                $metadata_attrs = '';
                if ($is_imported && $vote_id !== null && $answer_question_index !== null) {
                    $metadata_attrs = sprintf(
                        'data-vote-id="%s" data-question-index="%s" data-answer-index="%s"',
                        esc_attr($vote_id),
                        esc_attr($answer_question_index),
                        esc_attr($option_index)
                    );
                }
                ?>
                <div class="<?php echo esc_attr($option_class); ?>" <?php echo $metadata_attrs; ?> style="margin-bottom: 5px;">
                    <input
                        type="text"
                        name="vs_questions[<?php echo esc_attr($index); ?>][options][]"
                        value="<?php echo esc_attr($option); ?>"
                        style="width: 90%;"
                        placeholder="Opção <?php echo ($option_index + 1); ?>"
                    >
                    
                    <?php if ($is_imported && $valor_real !== null): ?>
                        <!-- Campo oculto para valor real -->
                        <input type="hidden" 
                               name="vs_questions[<?php echo esc_attr($index); ?>][valores_reais][<?php echo esc_attr($option_index); ?>]" 
                               class="vs-valor-real" 
                               value="<?php echo esc_attr($valor_real); ?>">
                        
                        <!-- Span para mostrar o valor real -->
                        <span class="vs-valor-real-texto" style="font-size: 12px; color: #666; margin-left: 10px;">
                            <?php echo esc_html($valor_real); ?>
                        </span>
                    <?php endif; ?>
                    <button type="button" class="vs-remove-option">Remover</button>
                </div>
                <?php
            }
            ?>
            <button type="button" class="button vs-add-option"
                data-question-index="<?php echo esc_attr($index); ?>">
                Adicionar Opção
            </button>
        </div>
    </div>

    <br>
    <label style="display: block; margin-top: 10px;">
        <input
            type="checkbox"
            name="vs_questions[<?php echo esc_attr($index); ?>][obrigatoria]"
            value="1"
            <?php checked(($question['obrigatoria'] ?? true), true); ?>
        >
        Pergunta Obrigatória
    </label>

    <button type="button" class="button button-secondary vs-remove-pergunta" style="margin-top:10px;">
        Remover Pergunta
    </button>
</div>

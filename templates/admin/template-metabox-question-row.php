<?php
/**
 * Template para una fila de pregunta en el metabox
 * 
 * Variables disponibles:
 * - $index: Índice de la pregunta
 * - $pergunta: Array con datos de la pregunta
 * 
 * @package VotingSystem\Templates\Admin
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="vs-pergunta">
    <div class="vs-pergunta-preview">
        <h5>Pergunta <?php echo esc_html($index + 1); ?></h5>
        <p><?php echo esc_html($pergunta['label'] ?? ''); ?></p>
        <?php if (!empty($pergunta['votacao_anterior_id'])): ?>
        <button type="button" class="button vs-importar-respostas" 
            data-votacao-id="<?php echo esc_attr($pergunta['votacao_anterior_id']); ?>" 
            data-pergunta-index="<?php echo esc_attr($index); ?>"
            data-status="<?php echo esc_attr($pergunta['status'] ?? ''); ?>">
            Importar respostas
        </button>
        <?php endif; ?>
    </div>
    <label>Pergunta:</label><br>
    <input
        type="text"
        name="vs_perguntas[<?php echo esc_attr($index); ?>][label]"
        value="<?php echo esc_attr($pergunta['label'] ?? ''); ?>"
        style="width: 100%;"
        placeholder="Digite sua pergunta aqui..."
    ><br><br>

    <label>Tipo de Resposta:</label><br>
    <select
        name="vs_perguntas[<?php echo esc_attr($index); ?>][tipo]"
        class="vs-tipo-campo"
    >
        <?php
        $tipos = [
            'texto' => 'Texto Livre', 
            'select' => 'Select', 
            'radio' => 'Radio', 
            'checkbox' => 'Checkbox',
            'votacao_anterior' => 'A partir de uma votação anterior...'
        ];
        foreach ($tipos as $val => $label) {
            $selected = (($pergunta['tipo'] ?? '') === $val) ? 'selected' : '';
            echo "<option value='{$val}' {$selected}>{$label}</option>";
        }
        ?>
    </select>

    <!-- Campo para armazenar ID da votação anterior -->
    <input
        type="hidden"
        name="vs_perguntas[<?php echo esc_attr($index); ?>][votacao_anterior_id]"
        class="vs-votacao-anterior-id"
        value="<?php 
        // Verifica se existem respostas importadas
        $respostas_importadas = $pergunta['respostas_importadas'] ?? wp_json_encode(['perguntas' => []]);
        $respostas_data = json_decode($respostas_importadas, true);
        
        // Se houver perguntas importadas, pega o ID da primeira pergunta
        if (!empty($respostas_data['perguntas'][0]['pergunta_origem'])) {
            echo esc_attr($respostas_data['perguntas'][0]['pergunta_origem']);
        } else {
            echo esc_attr($pergunta['votacao_anterior_id'] ?? '');
        }
    ?>"
    >

    <!-- Campo oculto para armazenar respostas importadas em JSON -->
    <input type="hidden"
        name="vs_perguntas[<?php echo esc_attr($index); ?>][respostas_importadas]"
        class="vs-respostas-importadas"
        value="<?php echo esc_attr($pergunta['respostas_importadas'] ?? wp_json_encode(['perguntas' => []])); ?>">

    <!-- Botão para abrir modal (visível apenas quando tipo 'votacao_anterior' estiver selecionado) -->
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

            <div id="vs-lista-votacoes" class="vs-modal-list">
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

    <!-- Campo oculto para valor unificado -->
    <input
        type="hidden"
        name="vs_perguntas[<?php echo esc_attr($index); ?>][unificada]"
        value="<?php echo esc_attr($pergunta['unificada'] ?? ''); ?>"
    >

    <!-- Container de opções (para select, radio, checkbox e votacao_anterior) -->
    <div class="vs-opcoes-container" style="display: <?php echo in_array($pergunta['tipo'] ?? '', ['radio', 'checkbox', 'select', 'votacao_anterior']) ? 'block' : 'none'; ?>;">
        <?php if (($pergunta['tipo'] ?? '') === 'votacao_anterior'): ?>
        <div class="vs-opcoes-grid">
            <!-- Coluna 1: Interface de Seleção -->
            <div class="vs-coluna-importadas">
                <h3>Respostas Importadas</h3>
                <div class="vs-tabela-container">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 26px; text-align: center;">#</th>
                                <th class="check-column">
                                    <input type="checkbox" class="vs-selecionar-todas-respostas">
                                </th>
                                <th>Resposta</th>
                                <th>Qtd. de Votos</th>
                                <th>Pergunta</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Será preenchido via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Coluna 2: Opções Selecionadas -->
            <div class="vs-coluna-opcoes">
                <label>Opções:</label><br>
                <div class="vs-opcoes">
                    <?php
                    $opcoes = $pergunta['opcoes'] ?? [''];
                    foreach ($opcoes as $opcao_index => $opcao) {
                        $valor_real = $pergunta['valores_reais'][$opcao_index] ?? $opcao;
                        ?>
                        <div class="vs-opcao-item" style="margin-bottom: 5px;">
                            <input
                                type="text"
                                name="vs_perguntas[<?php echo esc_attr($index); ?>][opcoes][]"
                                value="<?php echo esc_attr($opcao); ?>"
                                style="width: 90%;"
                                placeholder="Opção <?php echo ($opcao_index + 1); ?>"
                            >
                            <input
                                type="hidden"
                                name="vs_perguntas[<?php echo esc_attr($index); ?>][valores_reais][]"
                                value="<?php echo esc_attr($valor_real); ?>"
                                class="vs-valor-real"
                            >
                            <span class="vs-valor-real-texto"><?php echo esc_html($valor_real); ?></span>
                            <button type="button" class="button button-small vs-remove-opcao">Remover</button>
                            <span class="vs-pergunta-origem" style="color: #666; font-size: 0.9em; margin-left: 10px;">
                                <?php echo esc_html($pergunta['pergunta_origem'] ?? ''); ?>
                            </span>
                        </div>
                        <?php
                    }
                    ?>
                    <button type="button" class="button vs-add-opcao"
                        data-pergunta-index="<?php echo esc_attr($index); ?>">
                        Adicionar Opção
                    </button>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Estrutura existente para outros tipos de campo -->
        <label>Opções:</label><br>
        <div class="vs-opcoes">
            <?php
            $opcoes = $pergunta['opcoes'] ?? [''];
            foreach ($opcoes as $opcao_index => $opcao) {
                ?>
                <div class="vs-opcao-item" style="margin-bottom: 5px;">
                    <input
                        type="text"
                        name="vs_perguntas[<?php echo esc_attr($index); ?>][opcoes][]"
                        value="<?php echo esc_attr($opcao); ?>"
                        style="width: 90%;"
                        placeholder="Opção <?php echo ($opcao_index + 1); ?>"
                    >
                    <button type="button" class="button button-small vs-remove-opcao">Remover</button>
                </div>
                <?php
            }
            ?>
            <button type="button" class="button vs-add-opcao"
                data-pergunta-index="<?php echo esc_attr($index); ?>">
                Adicionar Opção
            </button>
        </div>
        <?php endif; ?>
    </div>

    <br>
    <label style="display: block; margin-top: 10px;">
        <input
            type="checkbox"
            name="vs_perguntas[<?php echo esc_attr($index); ?>][obrigatoria]"
            value="1"
            <?php checked(($pergunta['obrigatoria'] ?? true), true); ?>
        >
        Pergunta Obrigatória
    </label>

    <button type="button" class="button button-secondary vs-remove-pergunta" style="margin-top:10px;">
        Remover Pergunta
    </button>
</div>
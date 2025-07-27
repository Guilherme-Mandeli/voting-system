<?php
/**
 * Vista del metabox de preguntas de votación
 * 
 * @package VotingSystem\Metaboxes\Views
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renderiza el contenido del metabox de preguntas
 *
 * @param WP_Post $post Objeto del post actual
 */
function vs_render_metabox_questions_view($post) {
    $perguntas = get_post_meta($post->ID, 'vs_perguntas', true);
    wp_nonce_field('vs_salvar_perguntas', 'vs_nonce_perguntas');

    // Obtiene valor guardado para permitir edición
    $permitir_edicao = get_post_meta($post->ID, 'vs_permitir_edicao', true);

    ?>
    <div class="vs-metabox-questions">
        <!-- Opción para permitir editar voto -->
        <div class="vs-edit-permission-section">
            <p style="font-size: small; color: #555555; margin-bottom: 4px;">
                <label>
                    <input type="checkbox" name="vs_permitir_edicao" value="1" <?php checked($permitir_edicao, '1'); ?>>
                    Permitir editar voto
                </label>  
            </p>
            <p style="margin-top: 4px; padding: 6px 6px 8px 12px; border-left: 1px solid #ccc; background: #f6f7f7;">
                Habilitar esta opción permitirá que los usuarios editen sus respuestas después de la primera submisión.
            </p>
        </div>

        <hr style="margin: 20px 0; border: none; border-top: 1px solid #ccc;" />

        <!-- Container de preguntas -->
        <div id="vs-perguntas-wrapper">
            <?php if (!empty($perguntas) && is_array($perguntas)) : ?>
                <?php foreach ($perguntas as $index => $pergunta) : ?>
                    <?php vs_render_question_row_template($index, $pergunta); ?>
                <?php endforeach; ?>
            <?php else : ?>
                <?php vs_render_question_row_template(0, ['obrigatoria' => true]); ?>
            <?php endif; ?>
        </div>

        <!-- Botón para agregar pregunta -->
        <button type="button" class="button" id="vs-add-pergunta">
            Adicionar Pergunta
        </button>
    </div>

    <?php vs_render_metabox_questions_scripts($post); ?>
    <?php vs_render_metabox_questions_styles(); ?>
    <?php
}

/**
 * Renderiza una fila de pregunta usando el template
 *
 * @param int $index Índice de la pregunta
 * @param array $pergunta Datos de la pregunta
 */
function vs_render_question_row_template($index, $pergunta = []) {
    include VS_PLUGIN_PATH . 'templates/admin/template-metabox-question-row.php';
}

/**
 * Renderiza los scripts JavaScript para el metabox
 *
 * @param WP_Post $post Objeto del post actual
 */
function vs_render_metabox_questions_scripts($post) {
    $last_index = 0;
    $perguntas = get_post_meta($post->ID, 'vs_perguntas', true);
    if (!empty($perguntas) && is_array($perguntas)) {
        $last_index = count($perguntas);
    }
    ?>
    <script>
        (function($) {
            const wrapper = document.getElementById('vs-perguntas-wrapper');
            const addBtn = document.getElementById('vs-add-pergunta');
            let perguntaIndex = <?php echo $last_index; ?>;

            // Agregar nueva pregunta
            addBtn.addEventListener('click', function () {
                fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=vs_get_pergunta_template&index=' + perguntaIndex)
                    .then(res => res.text())
                    .then(html => {
                        wrapper.insertAdjacentHTML('beforeend', html);
                        perguntaIndex++;
                    })
                    .catch(error => {
                        console.error('Error al cargar template de pregunta:', error);
                    });
            });

            // Mostrar/ocultar opciones según tipo de campo
            document.addEventListener('change', function (e) {
                if (e.target && e.target.classList.contains('vs-tipo-campo')) {
                    const container = e.target.closest('.vs-pergunta');
                    const opcoesDiv = container.querySelector('.vs-opcoes-container');
                    if (['radio', 'checkbox', 'select'].includes(e.target.value)) {
                        opcoesDiv.style.display = 'block';
                    } else {
                        opcoesDiv.style.display = 'none';
                    }
                }
            });

            // Event delegation para botones dinámicos
            document.addEventListener('click', function (e) {
                // Remover pregunta
                if (e.target && e.target.classList.contains('vs-remove-pergunta')) {
                    const bloque = e.target.closest('.vs-pergunta');
                    if (confirm('¿Está seguro de que desea eliminar esta pregunta?')) {
                        bloque.remove();
                    }
                }

                // Remover opción
                if (e.target && e.target.classList.contains('vs-remove-opcao')) {
                    const opcao = e.target.closest('.vs-opcao-item');
                    opcao.remove();
                }

                // Agregar opción
                if (e.target && e.target.classList.contains('vs-add-opcao')) {
                    const perguntaIndex = e.target.getAttribute('data-pergunta-index');
                    const opcoesContainer = e.target.closest('.vs-opcoes');
                    const opcaoCount = opcoesContainer.querySelectorAll('.vs-opcao-item').length;
                    
                    const newOpcaoHTML = `
                        <div class='vs-opcao-item' style='margin-bottom: 5px;'>
                            <input type='text' 
                                   name='vs_perguntas[${perguntaIndex}][opcoes][]' 
                                   style='width: 90%;'
                                   placeholder='Opción ${opcaoCount + 1}'>
                            <button type='button' class='button button-small vs-remove-opcao'>Remover</button>
                        </div>
                    `;
                    
                    e.target.insertAdjacentHTML('beforebegin', newOpcaoHTML);
                }
            });

        })(jQuery);
    </script>
    <?php
}

/**
 * Renderiza los estilos CSS para el metabox
 */
function vs_render_metabox_questions_styles() {
    ?>
    <style>
        .vs-metabox-questions {
            padding: 10px 0;
        }
        
        .vs-edit-permission-section {
            margin-bottom: 15px;
        }
        
        .vs-pergunta {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            background: #fafafa;
            border-radius: 4px;
        }
        
        .vs-pergunta:hover {
            border-color: #ccc;
        }
        
        .vs-pergunta label {
            font-weight: 600;
            color: #333;
        }
        
        .vs-pergunta input[type="text"],
        .vs-pergunta select {
            border: 1px solid #ddd;
            border-radius: 3px;
            padding: 6px 8px;
            min-width: 125px;
        }
        
        .vs-pergunta input[type="text"]:focus,
        .vs-pergunta select:focus {
            border-color: #0073aa;
            box-shadow: 0 0 0 1px #0073aa;
            outline: none;
        }
        
        .vs-opcoes-container {
            margin-top: 10px;
            padding: 10px;
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 3px;
        }
        
        .vs-opcao-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .vs-opcao-item input {
            flex: 1;
        }
        
        .vs-add-opcao {
            margin-top: 8px;
        }
        
        .vs-remove-pergunta {
            background: #dc3232;
            border-color: #dc3232;
            color: white;
        }
        
        .vs-remove-pergunta:hover {
            background: #a00;
            border-color: #a00;
        }
        
        .vs-remove-opcao {
            background: #f56565;
            border-color: #f56565;
            color: white;
            font-size: 11px;
            padding: 2px 8px;
        }
        
        .vs-remove-opcao:hover {
            background: #e53e3e;
            border-color: #e53e3e;
        }
        
        #vs-add-pergunta {
            background: #0073aa;
            border-color: #0073aa;
            color: white;
            padding: 8px 16px;
            font-weight: 500;
        }
        
        #vs-add-pergunta:hover {
            background: #005a87;
            border-color: #005a87;
        }
    </style>
    <?php
}
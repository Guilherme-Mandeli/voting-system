<?php
// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Template para o formulário de votação
 * 
 * @param array $questions Array com as perguntas da votação
 * @param int $votacao_id ID da votação
 * @param array $respostas Array com as respostas do usuário (se existirem)
 * @param bool $ja_votou Se o usuário já votou
 * @param bool $permitir_edicao Se a votação permite edição
 * @param string $encerrada Status da votação
 */

?>

<div class="vs-voting-form-container">
    <?php if ($ja_votou): ?>
        <!-- Modo Visualização das Respostas -->
        <div class="vs-view-mode" id="vs-view-mode" style="display: block;">
            <div class="vs-voting-completed">
                <h3>Você já participou desta votação</h3>
                <div class="vs-responses-readonly">
                    <?php echo vs_render_respostas_votacao($questions, $respostas); ?>
                </div>
                
                <?php if ($permitir_edicao === '1'): ?>
                    <div class="vs-edit-actions">
                        <button type="button" class="vs-btn vs-btn-edit" id="vs-btn-edit">
                            ✏️ Editar meu voto
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($permitir_edicao === '1'): ?>
            <!-- Modo Edição (Inicialmente Oculto) -->
            <div class="vs-edit-mode" id="vs-edit-mode" style="display: none;">
                <div class="vs-edit-header">
                    <h3>Editando sua votação</h3>
                    <p class="vs-edit-notice">
                        <strong>Atenção:</strong> Você pode editar suas respostas até <?php echo esc_html($data_fim_formatted ?? 'o encerramento'); ?>.
                    </p>
                </div>
                
                <div class="vs-form-container">
                    <?php echo vs_render_formulario_votacao($questions, $votacao_id, $respostas); ?>
                </div>
                
                <div class="vs-edit-controls">
                    <button type="button" class="vs-btn vs-btn-secondary" id="vs-btn-cancel">
                        ← Voltar sem salvar
                    </button>
                    <button type="submit" class="vs-btn vs-btn-primary" id="vs-btn-save" form="vs-voting-form">
                        💾 Salvar voto
                    </button>
                </div>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- Formulário para Primeira Votação -->
        <div class="vs-voting-form-active">
            <div class="vs-form-container">
                <?php echo vs_render_formulario_votacao($questions, $votacao_id, $respostas); ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
/* Estilos para o sistema de toggle */
.vs-voting-form-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.vs-edit-header {
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-left: 4px solid #007cba;
    border-radius: 4px;
}

.vs-edit-header h3 {
    margin: 0 0 10px 0;
    color: #007cba;
}

.vs-edit-notice {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.vs-edit-actions {
    margin-top: 20px;
    text-align: center;
}

.vs-edit-controls {
    margin-top: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    display: flex;
    gap: 15px;
    justify-content: space-between;
    align-items: center;
}

.vs-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50px;
    font-family: var(--vs-font-family);
    font-size: var(--vs-font-size-base);
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    min-height: 44px;
    box-sizing: border-box;
    position: relative;
    overflow: hidden;
    padding: 12px 18px 11px 18px;
    gap: 8px;
    text-decoration: none !important;
    line-height: 1em;
}

.vs-btn-edit {
    background: #007cba;
    color: white;
}

.vs-btn-edit:hover {
    background: #005a87;
    transform: translateY(-2px);
}

.vs-btn-secondary {
    background: #6c757d;
    color: white;
}

.vs-btn-secondary:hover {
    background: #545b62;
}

.vs-btn-primary {
    background: #28a745;
    color: white;
}

.vs-btn-primary:hover {
    background: #218838;
}

/* Animações de transição */
.vs-view-mode, .vs-edit-mode {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.vs-view-mode.hiding, .vs-edit-mode.hiding {
    opacity: 0;
    transform: translateY(-10px);
}

/* Responsividade */
@media (max-width: 768px) {
    .vs-edit-controls {
        flex-direction: column;
        gap: 10px;
    }
    
    .vs-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
(function($) {
    $(document).ready(function() {
        // Elementos
        const $viewMode = $('#vs-view-mode');
        const $editMode = $('#vs-edit-mode');
        const $btnEdit = $('#vs-btn-edit');
        const $btnCancel = $('#vs-btn-cancel');
        const $btnSave = $('#vs-btn-save');
        const $form = $('#vs-voting-form');
        
        // Estado original do formulário para reset
        let originalFormData = null;
        
        // Captura dados originais do formulário
        function captureOriginalData() {
            if ($form.length) {
                originalFormData = $form.serialize();
            }
        }
        
        // Restaura dados originais do formulário
        function restoreOriginalData() {
            if (originalFormData && $form.length) {
                // Reset todos os campos
                $form[0].reset();
                
                // Reaplica valores originais
                const params = new URLSearchParams(originalFormData);
                params.forEach((value, key) => {
                    const $field = $form.find(`[name="${key}"]`);
                    if ($field.length) {
                        if ($field.is(':radio') || $field.is(':checkbox')) {
                            $field.filter(`[value="${value}"]`).prop('checked', true);
                        } else {
                            $field.val(value);
                        }
                    }
                });
            }
        }
        
        // Toggle para modo edição
        function showEditMode() {
            captureOriginalData();
            
            $viewMode.addClass('hiding');
            setTimeout(() => {
                $viewMode.hide();
                $editMode.show().removeClass('hiding');
            }, 300);
        }
        
        // Toggle para modo visualização
        function showViewMode() {
            $editMode.addClass('hiding');
            setTimeout(() => {
                $editMode.hide();
                $viewMode.show().removeClass('hiding');
            }, 300);
        }
        
        // Event Listeners
        $btnEdit.on('click', function() {
            showEditMode();
        });
        
        $btnCancel.on('click', function() {
            // Confirma se há alterações não salvas
            if (originalFormData && $form.serialize() !== originalFormData) {
                if (confirm('Você tem alterações não salvas. Deseja realmente voltar sem salvar?')) {
                    restoreOriginalData();
                    showViewMode();
                }
            } else {
                showViewMode();
            }
        });
        
        // Validação antes do envio
        $form.on('submit', function(e) {
            let hasErrors = false;
            
            // Validar campos de texto, textarea, etc. com required
            const $requiredTextFields = $form.find('[required]:not(:radio)');
            $requiredTextFields.each(function() {
                const $field = $(this);
                if (!$field.val()) {
                    hasErrors = true;
                    $field.addClass('vs-error');
                } else {
                    $field.removeClass('vs-error');
                }
            });
            
            // Validar grupos de radio buttons com data-required
            const $requiredRadioGroups = $form.find('input[type="radio"][data-required="true"]');
            const radioGroupNames = [];
            
            $requiredRadioGroups.each(function() {
                const fieldName = $(this).attr('name');
                if (radioGroupNames.indexOf(fieldName) === -1) {
                    radioGroupNames.push(fieldName);
                }
            });
            
            radioGroupNames.forEach(function(fieldName) {
                const $radioGroup = $form.find(`input[name="${fieldName}"]`);
                const isChecked = $form.find(`input[name="${fieldName}"]:checked`).length > 0;
                
                if (!isChecked) {
                    hasErrors = true;
                    $radioGroup.addClass('vs-error');
                } else {
                    $radioGroup.removeClass('vs-error');
                }
            });
            
            if (hasErrors) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios.');
                return false;
            }
            
            // Adiciona loading state
            $btnSave.prop('disabled', true).html('💾 Salvando...');
        });
        
        // Remove erro ao digitar/selecionar
        $form.on('input change', '[required]', function() {
            const $field = $(this);
            
            if ($field.is(':radio')) {
                // Para radio buttons, remove erro de todo o grupo
                const fieldName = $field.attr('name');
                $form.find(`input[name="${fieldName}"]`).removeClass('vs-error');
            } else {
                $field.removeClass('vs-error');
            }
        });
        
        // Aviso antes de sair da página com alterações não salvas
        $(window).on('beforeunload', function() {
            if (originalFormData && $form.length && $form.serialize() !== originalFormData && $editMode.is(':visible')) {
                return 'Você tem alterações não salvas. Deseja realmente sair?';
            }
        });
    });
})(jQuery);
</script>

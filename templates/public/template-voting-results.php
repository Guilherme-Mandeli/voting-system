<?php
// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Template para exibição dos resultados da votação quando o usuário já votou
 * 
 * @param array $questions Array com as perguntas da votação
 * @param array $respostas Array com as respostas do usuário
 * @param bool $permitir_edicao Se a votação permite edição
 * @param int $votacao_id ID da votação
 */
?>

<div class="vs-voting-results">
    <div class="vs-results-header">
        <h3>Suas Respostas</h3>
        <p class="vs-results-message">Você já participou desta votação. Confira suas respostas abaixo:</p>
    </div>

    <div class="vs-results-table-container table-container">
        <table class="vs-results-table table-auto">
            <thead>
                <tr>
                    <th>Pergunta</th>
                    <th>Sua Resposta</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questions as $index => $question):
                    $label = esc_html($question['label']);
                    $resposta = isset($respostas['respostas'][$index]) ? $respostas['respostas'][$index] : '';

                    if (is_array($resposta)) {
                        $resposta = implode(', ', array_map('esc_html', $resposta));
                    } else {
                        $resposta = esc_html($resposta);
                    }
                ?>
                <tr>
                    <td class="vs-question-cell"><?php echo $label; ?></td>
                    <td class="vs-answer-cell"><?php echo $resposta ?: '<em>Não respondido</em>'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($permitir_edicao): ?>
    <div class="vs-edit-actions">
        <button type="button" class="vs-btn vs-btn-edit vs-btn-primary" onclick="vs_toggle_edit_mode()">
            <span class="vs-edit-icon"><i class="fa-regular fa-pen-to-square"></i></span>
            Editar meu voto
        </button>
    </div>
    <?php endif; ?>

    <!-- Formulário de edição (inicialmente oculto) -->
    <?php if ($permitir_edicao): ?>
    <div id="vs-edit-form" class="vs-edit-form" style="display: none;">
        <div class="vs-edit-header">
            <h4>Editar Suas Respostas</h4>
            <p>Modifique suas respostas abaixo e clique em "Salvar Voto":</p>
        </div>
        
        <?php 
        // Definir $user_responses para preenchimento automático
        $user_responses = [];
        if (isset($respostas['respostas']) && is_array($respostas['respostas'])) {
            $user_responses = $respostas['respostas'];
        }
        
        // Inclui o formulário de edição
        include VS_PLUGIN_PATH . 'templates/public/template-voting-form-fields.php';
        ?>
        
        <div class="vs-edit-actions">
            <button type="button" class="vs-btn vs-btn-cancel" onclick="vs_cancel_edit_mode()">
                Cancelar
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.vs-voting-results {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.vs-results-header {
    margin-bottom: 20px;
    text-align: center;
}

.vs-results-header h3 {
    color: #000000;
    margin-bottom: 10px;
}

.vs-results-message {
    color: #7f8c8d;
    font-style: italic;
}

.vs-results-table-container {
    overflow-x: auto;
    margin-bottom: 20px;
}

.vs-results-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}

.vs-results-table th {
    background: #0034ff;
    color: white;
    padding: 15px;
    text-align: left;
    font-weight: 600;
}

.vs-results-table td {
    padding: 15px;
    border-bottom: 1px solid #ecf0f1;
}

.vs-question-cell {
    font-weight: 500;
    color: #2c3e50;
    width: 40%;
}

.vs-answer-cell {
    color: #34495e;
}

.vs-results-table tr:last-child td {
    border-bottom: none;
}

.vs-results-table tr:hover {
    background-color: #f8f9fa;
}

.vs-edit-actions {
    text-align: center;
    margin-top: 20px;
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

.vs-btn:hover {
    transform: translateY(-1px);
}

.vs-btn-primary {
    background-color: #0034ff;
    color: #ffffff;
    border: 1px solid #0034ff;
}

.vs-btn-primary:hover {
    background-color: #ffffff;
    color: #0034ff;
    border: 1px solid #0034ff;
}

.vs-btn-secondary {
    background-color: transparent;
    color: #0034ff;
    border: 1px solid #0034ff;
}

.vs-btn-secondary:hover {
    background-color: #0034ff;
    color: #ffffff;
    border: 1px solid #0034ff;
}

.vs-btn-link {
    background-color: transparent;
    color: #0034ff;
    border: 1px solid transparent;
}

.vs-btn-link:hover {
    background-color: #f1f5f9;
    color: #0034ff;
    border: 1px solid #0034ff;
}

.vs-btn:disabled {
    background-color: #e5e7eb;
    color: #9ca3af;
    border: 1px solid #e5e7eb;
    cursor: not-allowed;
}

.vs-btn-cancel {
    background: #95a5a6;
    color: white;
    border: none;
}

.vs-edit-form {
    margin-top: 30px;
    border-top: 1px solid #ccc;
    padding-top: 30px;
    margin-top: 31px;
}

.vs-edit-header {
    margin-bottom: 20px;
}

.vs-edit-header h4 {
    color: #000000;
    margin-bottom: 10px;
}

.vs-edit-icon {
    margin-right: 4px;
}

@media (max-width: 768px) {
    .vs-voting-results {
        padding: 15px;
    }
    
    .vs-results-table th,
    .vs-results-table td {
        padding: 10px;
    }
    
    .vs-btn {
        padding: 10px 20px;
        font-size: 14px;
    }
}
</style>

<script>
function vs_toggle_edit_mode() {
    const resultsTable = document.querySelector('.vs-results-table-container');
    const editForm = document.getElementById('vs-edit-form');
    const editButton = document.querySelector('.vs-btn-edit');
    
    if (resultsTable && editForm) {
        resultsTable.style.display = 'none';
        editForm.style.display = 'block';
        editButton.style.display = 'none';
    }
}

function vs_cancel_edit_mode() {
    const resultsTable = document.querySelector('.vs-results-table-container');
    const editForm = document.getElementById('vs-edit-form');
    const editButton = document.querySelector('.vs-btn-edit');
    
    if (resultsTable && editForm) {
        resultsTable.style.display = 'block';
        editForm.style.display = 'none';
        editButton.style.display = 'inline-block';
    }
}
</script>

<?php
/**
 * Template para el metabox de información de votación
 * 
 * Variables disponibles:
 * - $post: Objeto WP_Post actual
 * - $ano: Año de la votación
 * - $status: Status de la votación
 * - $codigo: Código de la votación
 * - $data_inicio: Fecha de inicio
 * - $data_fim: Fecha de fin
 * - $quantidade_votos: Cantidad de votos
 * 
 * @package VotingSystem\Templates\Admin
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="vs-voting-info-metabox">
    <?php wp_nonce_field('vs_info_metabox_nonce', 'vs_info_metabox_nonce_field'); ?>
    
    <div class="vs-field-group">
        <label for="vs_ano"><strong>Ano:</strong></label>
        <input 
            type="text" 
            id="vs_ano"
            name="vs_ano" 
            value="<?php echo esc_attr($ano); ?>" 
            style="width: 100%;" 
            pattern="\d{4}" 
            title="Digite um ano com 4 dígitos, ex: <?php echo date('Y'); ?>"
            maxlength="4"
            placeholder="<?php echo date('Y'); ?> (4 dígitos)"
        >
    </div>

    <div class="vs-field-group">
        <label for="vs_status"><strong>Status:</strong></label>
        <select id="vs_status" name="vs_status" style="width: 100%;">
            <?php
            $options_status = [
                'aberta' => 'Aberta',
                'em_pausa' => 'Em Pausa',
                'encerrada' => 'Encerrada'
            ];
            foreach ($options_status as $val => $label) {
                echo '<option value="' . esc_attr($val) . '" ' . selected($val, $status, false) . '>' . esc_html($label) . '</option>';
            }
            ?>
        </select>
    </div>

    <div class="vs-field-group">
        <label for="vs_codigo"><strong>Código da Votação:</strong></label>
        <input 
            type="text" 
            id="vs_codigo"
            name="vs_codigo" 
            value="<?php echo esc_attr($codigo); ?>" 
            style="width: 100%;"
            placeholder="VOT-<?php echo esc_attr($ano); ?>-1"
        >
        <p class="description">Código único para identificar a votação</p>
    </div>

    <div class="vs-field-group">
        <label for="vs_data_inicio"><strong>Data de Início:</strong></label>
        <input 
            type="date" 
            id="vs_data_inicio"
            name="vs_data_inicio" 
            value="<?php echo esc_attr($data_inicio); ?>" 
            style="width: 100%;"
        >
        <p class="description">Data de início da votação</p>
    </div>

    <div class="vs-field-group">
        <label for="vs_data_fim"><strong>Data de Término:</strong></label>
        <input 
            type="date" 
            id="vs_data_fim"
            name="vs_data_fim" 
            value="<?php echo esc_attr($data_fim); ?>" 
            style="width: 100%;"
        >
        <p class="description">Data de finalização da votação</p>
    </div>

    <div class="vs-field-group">
        <label for="vs_qtd_votos"><strong>Quantidade de votos recebidos:</strong></label>
        <input 
            type="number" 
            id="vs_qtd_votos"
            value="<?php echo intval($quantidade_votos); ?>" 
            style="width: 100%;" 
            readonly
            class="vs-readonly-field"
        >
    </div>
</div>

<style>
.vs-voting-info-metabox {
    padding: 10px 0;
}

.vs-field-group {
    margin-bottom: 15px;
}

.vs-field-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #333;
}

.vs-field-group input,
.vs-field-group select {
    border: 1px solid #ddd;
    border-radius: 3px;
    padding: 6px 8px;
    font-size: 14px;
}

.vs-field-group input:focus,
.vs-field-group select:focus {
    border-color: #0073aa;
    box-shadow: 0 0 0 1px #0073aa;
    outline: none;
}

.vs-readonly-field {
    background-color: #f9f9f9;
    color: #666;
}

.vs-field-group .description {
    margin: 5px 0 0 0;
    font-size: 12px;
    color: #666;
    font-style: italic;
}
</style>
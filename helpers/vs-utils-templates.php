<?php
defined( 'ABSPATH' ) || exit;

/**
 * Renderiza o formulário de votação com perguntas dinâmicas.
 */
function vs_render_formulario_votacao($perguntas, $votacao_id, $respostas = []) {
    ob_start();
    include VS_PLUGIN_PATH . 'templates/public/template-voting-form-fields.php';
    return ob_get_clean();
}

/**
 * Renderiza as respostas do usuário em modo leitura.
 */
function vs_render_respostas_votacao($perguntas, $respostas) {
    ob_start();
    include VS_PLUGIN_PATH . 'templates/public/template-voting-responses.php';
    return ob_get_clean();
}
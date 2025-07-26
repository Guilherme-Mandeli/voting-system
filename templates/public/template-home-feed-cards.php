<?php
// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Template para exibição dos cards de votações
 * 
 * @param array $agrupado Array com votações agrupadas por ano e evento
 */
?>

<?php if (empty($agrupado)): ?>
    <p>Nenhuma votação encontrada.</p>
<?php else: ?>
    <?php foreach ($agrupado as $ano => $eventos): ?>
        <h2 class='vs-bloco-ano'>Ano: <?php echo esc_html($ano); ?></h2>
        <?php foreach ($eventos as $evento_nome => $votacoes): ?>
            <h3 class='vs-bloco-evento'>Evento: <?php echo esc_html($evento_nome); ?></h3>
            <div class='vs-cards-container'>
                <?php foreach ($votacoes as $v): 
                    $status = strtolower($v['status']);
                    $class_status = 'vs-status-' . $status;
                    $btn_disabled = ($status !== 'aberta' || !$v['publicado']) ? 'vs-btn-disabled' : '';
                ?>
                <div class="vs-card <?php echo esc_attr($status); ?>">
                    <div class="vs-card-header">
                        <span class="vs-badge <?php echo esc_attr($class_status); ?>">
                            <?php echo ucfirst($status); ?>
                        </span>
                    </div>
                    <h4><?php echo esc_html($v['title']); ?></h4>
                    <p><?php echo esc_html($v['excerpt']); ?></p>
                    <a class="vs-card-button <?php echo $btn_disabled; ?>" href="<?php echo esc_url($v['link']); ?>" <?php echo $btn_disabled ? 'onclick="return false;"' : ''; ?>>
                        Ver Votação
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
<?php endif; ?>
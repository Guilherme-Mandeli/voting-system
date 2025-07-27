<?php
/**
 * Template para layout de lista do shortcode genérico
 */
defined('ABSPATH') || exit;
?>

<div class="vs-generic-container vs-layout-list">
    <?php if ($show_filters): ?>
        <div class="vs-generic-filters">
            <!-- Mesmo filtro do template cards -->
            <?php include 'template-votacoes-generic-filters.php'; ?>
        </div>
    <?php endif; ?>
    
    <div class="vs-generic-content">
        <?php if ($group_by === 'none'): ?>
            <!-- Layout simples sem agrupamento -->
            <div class="vs-list-container">
                <?php foreach ($votacoes as $votacao): ?>
                    <?php include 'template-votacoes-generic-list-item.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Layout com agrupamento -->
            <?php foreach ($votacoes as $grupo_nome => $grupo_votacoes): ?>
                <div class="vs-group-section">
                    <h3 class="vs-group-title"><?php echo esc_html($grupo_nome); ?></h3>
                    <div class="vs-list-container">
                        <?php foreach ($grupo_votacoes as $votacao): ?>
                            <?php include 'template-votacoes-generic-list-item.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (empty($votacoes) || (is_array($votacoes) && empty(array_filter($votacoes)))): ?>
            <div class="vs-no-results">
                <p>Nenhuma votação encontrada com os critérios especificados.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

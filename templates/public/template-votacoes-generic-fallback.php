<?php
/**
 * Template fallback para o shortcode genérico
 */
defined('ABSPATH') || exit;
?>

<div class="vs-generic-container vs-layout-fallback">
    <div class="vs-generic-content">
        <?php if (!empty($votacoes)): ?>
            <div class="vs-simple-list">
                <?php 
                $items = $group_by === 'none' ? $votacoes : array_merge(...array_values($votacoes));
                foreach ($items as $votacao): 
                ?>
                    <div class="vs-simple-item">
                        <h4><a href="<?php echo esc_url($votacao['link']); ?>"><?php echo esc_html($votacao['title']); ?></a></h4>
                        <?php if (!empty($votacao['excerpt'])): ?>
                            <p><?php echo esc_html($votacao['excerpt']); ?></p>
                        <?php endif; ?>
                        <span class="vs-status"><?php echo esc_html($votacao['status_display']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Nenhuma votação encontrada.</p>
        <?php endif; ?>
    </div>
</div>

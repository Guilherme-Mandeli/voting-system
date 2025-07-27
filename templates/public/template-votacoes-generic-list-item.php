<?php
/**
 * Template para item individual de lista
 */
defined('ABSPATH') || exit;
?>

<div class="vs-list-item <?php echo $votacao['ja_votou'] ? 'vs-item-voted' : ''; ?> <?php echo $votacao['is_encerrada'] ? 'vs-item-closed' : ''; ?>">
    <div class="vs-list-content">
        <div class="vs-list-header">
            <h4 class="vs-list-title">
                <a href="<?php echo esc_url($votacao['link']); ?>">
                    <?php echo esc_html($votacao['title']); ?>
                </a>
            </h4>
            
            <div class="vs-list-badges">
                <span class="vs-status-badge vs-status-<?php echo esc_attr($votacao['status']); ?>">
                    <?php echo esc_html($votacao['status_display']); ?>
                </span>
                
                <?php if ($votacao['show_participation_badge'] && $votacao['ja_votou']): ?>
                    <span class="vs-participation-badge">✓ Já votou</span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($votacao['excerpt'])): ?>
            <p class="vs-list-excerpt">
                <?php echo esc_html($votacao['excerpt']); ?>
            </p>
        <?php endif; ?>
        
        <div class="vs-list-meta">
            <?php if (!empty($votacao['tempo_restante'])): ?>
                <span class="vs-meta-item">
                    <span class="vs-meta-icon">⏰</span>
                    <?php echo esc_html($votacao['tempo_restante']); ?>
                </span>
            <?php endif; ?>
            
            <?php if ($votacao['data_fim'] && empty($votacao['tempo_restante'])): ?>
                <span class="vs-meta-item">
                    <span class="vs-meta-icon">📅</span>
                    Prazo: <?php echo esc_html(date('d/m/Y', strtotime($votacao['data_fim']))); ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($votacao['show_actions'] === 'visible'): ?>
        <div class="vs-list-actions">
            <?php if (!$votacao['is_encerrada']): ?>
                <?php if ($votacao['ja_votou']): ?>
                    <a href="<?php echo esc_url($votacao['link']); ?>" class="vs-btn vs-btn-secondary vs-btn-sm">
                        Ver Respostas
                    </a>
                    <?php if ($votacao['pode_editar']): ?>
                        <a href="<?php echo esc_url($votacao['link']); ?>" class="vs-btn vs-btn-edit vs-btn-sm">
                            Editar Voto
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?php echo esc_url($votacao['link']); ?>" class="vs-btn vs-btn-primary vs-btn-sm">
                        Participar
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <?php if ($votacao['ja_votou']): ?>
                    <a href="<?php echo esc_url($votacao['link']); ?>" class="vs-btn vs-btn-secondary vs-btn-sm">
                        Ver Respostas
                    </a>
                <?php else: ?>
                    <span class="vs-btn vs-btn-disabled vs-btn-sm">Encerrada</span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

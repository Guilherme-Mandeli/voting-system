<?php
/**
 * Template para item individual de card
 */
defined('ABSPATH') || exit;
?>

<div class="vs-card <?php echo $votacao['ja_votou'] ? 'vs-card-voted' : ''; ?> <?php echo $votacao['is_encerrada'] ? 'vs-card-closed' : ''; ?>">
    <div class="vs-card-header">
        <h4 class="vs-card-title">
            <a href="<?php echo esc_url($votacao['link']); ?>">
                <?php echo esc_html($votacao['title']); ?>
            </a>
        </h4>
        
        <div class="vs-card-badges">
            <span class="vs-status-badge vs-status-<?php echo esc_attr($votacao['status']); ?>">
                <?php echo esc_html($votacao['status_display']); ?>
            </span>
            
            <?php if ($votacao['show_participation_badge'] && $votacao['ja_votou']): ?>
                <span class="vs-participation-badge">✓ Já votou</span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="vs-card-body">
        <?php if (!empty($votacao['excerpt'])): ?>
            <p class="vs-card-excerpt">
                <?php echo esc_html($votacao['excerpt']); ?>
            </p>
        <?php endif; ?>
        
        <?php if (!empty($votacao['tempo_restante'])): ?>
            <div class="vs-card-meta">
                <span class="vs-meta-icon">⏰</span>
                <span class="vs-meta-text"><?php echo esc_html($votacao['tempo_restante']); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($votacao['data_fim'] && empty($votacao['tempo_restante'])): ?>
            <div class="vs-card-meta">
                <span class="vs-meta-icon">📅</span>
                <span class="vs-meta-text">
                    Prazo: <?php echo esc_html(date('d/m/Y', strtotime($votacao['data_fim']))); ?>
                </span>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($votacao['show_actions'] === 'visible'): ?>
        <div class="vs-card-actions">
            <?php if (!$votacao['is_encerrada']): ?>
                <?php if ($votacao['ja_votou']): ?>
                    <a href="<?php echo esc_url($votacao['link']); ?>" class="vs-btn vs-btn-secondary">
                        Ver Respostas
                    </a>
                    <?php if ($votacao['pode_editar']): ?>
                        <a href="<?php echo esc_url($votacao['link']); ?>" class="vs-btn vs-btn-edit">
                            Editar Voto
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?php echo esc_url($votacao['link']); ?>" class="vs-btn vs-btn-primary">
                        Participar
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <?php if ($votacao['ja_votou']): ?>
                    <a href="<?php echo esc_url($votacao['link']); ?>" class="vs-btn vs-btn-secondary">
                        Ver Respostas
                    </a>
                <?php else: ?>
                    <span class="vs-btn vs-btn-disabled">Encerrada</span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

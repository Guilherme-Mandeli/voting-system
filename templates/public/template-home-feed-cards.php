<?php
/**
 * Template para exibição dos cards de votações
 * Design inspirado em Fluent Design + Material UI
 * Suporte a funcionalidades avançadas: tempo restante, badges inteligentes, etc.
 */

defined('ABSPATH') || exit;
?>

<div class="vs-home-cards-container">
    <?php if (empty($agrupado)): ?>
        <div class="vs-empty-state">
            <div class="vs-empty-icon">📊</div>
            <h3>Nenhuma votação encontrada</h3>
            <p>Não há votações que correspondam aos filtros selecionados.</p>
        </div>
    <?php else: ?>
        <?php foreach ($agrupado as $ano => $categorias): ?>
            <div class="vs-ano-section">
                <h2 class="vs-ano-titulo">
                    <span class="vs-ano-icon">📅</span>
                    <?php echo esc_html($ano); ?>
                </h2>
                
                <?php foreach ($categorias as $categoria_nome => $votacoes): ?>
                    <div class="vs-categoria-section">
                        <h3 class="vs-categoria-titulo">
                            <span class="vs-categoria-icon">
                                <?php echo $categoria_nome === 'Sem Categoria' ? '📋' : '🏷️'; ?>
                            </span>
                            <?php echo esc_html($categoria_nome); ?>
                            <span class="vs-categoria-count">(<?php echo count($votacoes); ?>)</span>
                        </h3>
                        
                        <div class="vs-cards-grid">
                            <?php foreach ($votacoes as $votacao): ?>
                                <div class="vs-card <?php echo esc_attr('vs-status-' . $votacao['status']); ?> <?php echo $votacao['ja_votou'] ? 'vs-card-voted' : ''; ?>">
                                    <div class="vs-card-header">
                                        <div class="vs-card-badges">
                                            <span class="vs-badge vs-badge-status vs-badge-<?php echo esc_attr($votacao['status']); ?>">
                                                <?php echo esc_html($votacao['status_display']); ?>
                                            </span>
                                            
                                            <?php if ($votacao['show_participation_badge'] && $votacao['ja_votou']): ?>
                                                <span class="vs-badge vs-badge-voted">
                                                    ✓ Participou
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($votacao['tempo_restante']) && $votacao['tempo_restante'] !== 'Encerrada'): ?>
                                                <span class="vs-badge vs-badge-time">
                                                    ⏱️ <?php echo esc_html($votacao['tempo_restante']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="vs-card-content">
                                        <h4 class="vs-card-title">
                                            <?php echo esc_html($votacao['title']); ?>
                                        </h4>
                                        
                                        <?php if (!empty($votacao['excerpt'])): ?>
                                            <p class="vs-card-excerpt">
                                                <?php echo esc_html($votacao['excerpt']); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="vs-card-meta">
                                            <?php if ($votacao['data_fim']): ?>
                                                <div class="vs-meta-item">
                                                    <span class="vs-meta-icon">⏰</span>
                                                    <span class="vs-meta-text">
                                                        Prazo: <?php echo esc_html(date('d/m/Y', strtotime($votacao['data_fim']))); ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($votacao['tempo_restante']) && $votacao['tempo_restante'] !== 'Encerrada'): ?>
                                                <div class="vs-meta-item vs-meta-urgent">
                                                    <span class="vs-meta-icon">⚡</span>
                                                    <span class="vs-meta-text">
                                                        <?php echo esc_html($votacao['tempo_restante']); ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($votacao['show_actions'] === 'visible'): ?>
                                        <div class="vs-card-actions">
                                            <?php if ($votacao['status'] === 'aberta' && $votacao['publicado'] && !$votacao['is_encerrada']): ?>
                                                <?php if ($votacao['ja_votou']): ?>
                                                    <!-- Usuário já votou -->
                                                    <a href="<?php echo esc_url($votacao['link']); ?>" class="vs-btn vs-btn-secondary">
                                                        <span class="vs-btn-icon">👁️</span>
                                                        Ver Respostas
                                                    </a>
                                                    
                                                    <?php if ($votacao['pode_editar']): ?>
                                                        <a href="<?php echo esc_url($votacao['link']); ?>" class="vs-btn-link vs-btn-edit">
                                                            <span class="vs-btn-icon">✏️</span>
                                                            Editar voto
                                                        </a>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <!-- Usuário ainda não votou -->
                                                    <a href="<?php echo esc_url($votacao['link']); ?>" class="vs-btn vs-btn-primary">
                                                        <span class="vs-btn-icon">🗳️</span>
                                                        Participar
                                                    </a>
                                                <?php endif; ?>
                                            <?php elseif ($votacao['is_encerrada']): ?>
                                                <!-- Votação encerrada -->
                                                <a href="<?php echo esc_url($votacao['link']); ?>" class="vs-btn vs-btn-secondary">
                                                    <span class="vs-btn-icon">📊</span>
                                                    Ver Resultados
                                                </a>
                                            <?php else: ?>
                                                <!-- Votação indisponível -->
                                                <button class="vs-btn vs-btn-disabled" disabled>
                                                    <span class="vs-btn-icon">🔒</span>
                                                    <?php echo $votacao['status'] === 'em-pausa' ? 'Em pausa' : 'Indisponível'; ?>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
/**
 * Template para layout de cards do shortcode genérico
 */
defined('ABSPATH') || exit;
?>

<div class="vs-generic-container vs-layout-cards">
    <?php if ($show_filters): ?>
        <div class="vs-generic-filters">
            <form method="get" class="vs-filters-form">
                <div class="vs-filters-grid">
                    <?php if (!empty($eventos)): ?>
                        <div class="vs-filter-group">
                            <label for="evento">Categoria:</label>
                            <select name="evento" id="evento">
                                <option value="">Todas as categorias</option>
                                <?php foreach ($eventos as $evento): ?>
                                    <option value="<?php echo esc_attr($evento->slug); ?>" 
                                            <?php selected($filtro_evento, $evento->slug); ?>>
                                        <?php echo esc_html($evento->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($anos_disponiveis)): ?>
                        <div class="vs-filter-group">
                            <label for="ano">Ano:</label>
                            <select name="ano" id="ano">
                                <option value="">Todos os anos</option>
                                <?php foreach ($anos_disponiveis as $ano): ?>
                                    <option value="<?php echo esc_attr($ano); ?>" 
                                            <?php selected($filtro_ano, $ano); ?>>
                                        <?php echo esc_html($ano); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <div class="vs-filter-actions">
                        <button type="submit" class="vs-btn vs-btn-primary">Filtrar</button>
                        <?php if ($filtro_evento || $filtro_ano): ?>
                            <a href="?" class="vs-btn vs-btn-secondary">Limpar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    <?php endif; ?>
    
    <div class="vs-generic-content">
        <?php if ($group_by === 'none'): ?>
            <!-- Layout simples sem agrupamento -->
            <div class="vs-cards-grid">
                <?php foreach ($votacoes as $votacao): ?>
                    <?php include 'template-votacoes-generic-card-item.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Layout com agrupamento -->
            <?php foreach ($votacoes as $grupo_nome => $grupo_votacoes): ?>
                <div class="vs-group-section">
                    <h3 class="vs-group-title"><?php echo esc_html($grupo_nome); ?></h3>
                    <div class="vs-cards-grid">
                        <?php foreach ($grupo_votacoes as $votacao): ?>
                            <?php include 'template-votacoes-generic-card-item.php'; ?>
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

<?php
/**
 * Template para o formulário de filtros do feed de votações
 * Design inspirado em Fluent Design + Material UI
 */

defined('ABSPATH') || exit;
?>

<div class="vs-home-filtros-container">
    <form method="get" class="vs-home-filtros">
        <div class="vs-filtros-grid">
            <div class="vs-filtro-grupo">
                <label for="evento-filter" class="vs-filtro-label">
                    Evento
                </label>
                <select name="evento" id="evento-filter" class="vs-filtro-select">
                    <option value="">Todos os eventos</option>
                    <?php foreach ($eventos as $evento): ?>
                        <option value="<?php echo esc_attr($evento->slug); ?>" <?php selected($evento->slug, $filtro_evento); ?>>
                            <?php echo esc_html($evento->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="vs-filtro-grupo">
                <label for="ano-filter" class="vs-filtro-label">
                    Ano
                </label>
                <select name="ano" id="ano-filter" class="vs-filtro-select">
                    <option value="">Todos os anos</option>
                    <?php foreach ($anos_disponiveis as $ano): ?>
                        <option value="<?php echo esc_attr($ano); ?>" <?php selected($ano, $filtro_ano); ?>>
                            <?php echo esc_html($ano); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="vs-filtro-grupo vs-filtro-acoes">
                <button type="submit" class="vs-btn-filtrar">
                    Filtrar
                </button>
                
                <?php if ($filtro_evento || $filtro_ano): ?>
                    <a href="<?php echo esc_url(strtok($_SERVER['REQUEST_URI'], '?')); ?>" class="vs-btn-limpar">
                        Limpar
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

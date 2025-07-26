<?php
// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Template para o formulário de filtros do feed de votações
 * 
 * @param array $eventos Lista de termos da taxonomia eventos
 * @param array $anos_disponiveis Lista de anos disponíveis
 * @param string $filtro_evento Evento selecionado no filtro
 * @param string $filtro_ano Ano selecionado no filtro
 */
?>

<form method="get" class="vs-feed-filtros">
    <label>Evento:
        <select name="evento">
            <option value="">Todos</option>
            <?php foreach ($eventos as $evento): ?>
                <option value="<?php echo esc_attr($evento->slug); ?>" <?php selected($evento->slug, $filtro_evento); ?>>
                    <?php echo esc_html($evento->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Ano:
        <select name="ano">
            <option value="">Todos</option>
            <?php foreach ($anos_disponiveis as $ano): ?>
                <option value="<?php echo esc_attr($ano); ?>" <?php selected($ano, $filtro_ano); ?>>
                    <?php echo esc_html($ano); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <button type="submit">Filtrar</button>
</form>
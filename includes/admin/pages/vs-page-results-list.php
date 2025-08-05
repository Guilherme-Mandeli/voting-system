<?php
/**
 * Página administrativa de listagem de resultados das votações
 * 
 * @package VotingSystem\Admin\Pages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renderiza a página de listagem de resultados
 */
function vs_render_results_page() {
    // Obter filtros da requisição
    $ano = isset($_GET['filtro_ano']) ? sanitize_text_field($_GET['filtro_ano']) : '';
    $evento = isset($_GET['filtro_evento']) ? sanitize_text_field($_GET['filtro_evento']) : '';
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 30;

    // Montar args da query
    $args = [
        'post_type' => 'votacoes',
        'posts_per_page' => $per_page,
        'paged' => $paged,
    ];

    // Adicionar busca no título
    if (!empty($search)) {
        $args['s'] = $search;
    }

    $meta_query = [];
    if (!empty($ano)) {
        $meta_query[] = [
            'key' => '_vs_ano',
            'value' => $ano,
            'compare' => '='
        ];
    }

    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }

    if (!empty($evento)) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'eventos',
                'field' => 'slug',
                'terms' => $evento
            ]
        ];
    }

    $query = new WP_Query($args);

    echo '<div class="wrap">';
    echo '<h1>Resultados das Votações</h1>';

    // Filtros e busca
    echo '<form method="get" style="margin-bottom:20px;">';
    echo '<input type="hidden" name="post_type" value="votacoes" />';
    echo '<input type="hidden" name="page" value="votacoes_resultados" />';

    // Filtro por ano
    echo 'Ano: <select name="filtro_ano">';
    echo '<option value="">Todos</option>';
    $anos = vs_get_votacoes_years();
    foreach ($anos as $ano_option) {
        $selected = ($ano == $ano_option) ? 'selected' : '';
        echo "<option value='{$ano_option}' {$selected}>{$ano_option}</option>";
    }
    echo '</select> ';

    // Filtro por evento
    echo 'Evento: <select name="filtro_evento">';
    echo '<option value="">Todos</option>';
    $categorias = get_terms([
        'taxonomy' => 'eventos',
        'hide_empty' => false
    ]);
    foreach ($categorias as $cat) {
        $selected = ($evento == $cat->slug) ? 'selected' : '';
        echo "<option value='{$cat->slug}' {$selected}>{$cat->name}</option>";
    }
    echo '</select> ';

    // Campo de busca
    echo 'Buscar título: <input type="search" name="s" value="' . esc_attr($search) . '" /> ';

    echo '<input type="submit" class="button" value="Filtrar" />';
    echo '</form>';

    // Paginação - mostrar no topo também
    if ($query->have_posts()) {
        $pagination_links = paginate_links([
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'current' => $paged,
            'total' => $query->max_num_pages,
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
        ]);

        if ($pagination_links) {
            echo '<div class="tablenav"><div class="tablenav-pages">' . $pagination_links . '</div></div>';
        }

        // Tabela de votações
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>Nome</th>';
        echo '<th>Ano</th>';
        echo '<th>Evento</th>';
        echo '<th>Status</th>';
        echo '<th>Qtd Votos</th>';
        echo '<th>Ações</th>';
        echo '</tr></thead>';

        echo '<tbody>';
        while ($query->have_posts()) {
            $query->the_post();
            $id = get_the_ID();
            $ano_vot = get_post_meta($id, '_vs_ano', true);
            $status = get_post_meta($id, '_vs_status', true);
            $qtd_votos = get_post_meta($id, '_vs_qtd_votos', true);

            $eventos = get_the_terms($id, 'eventos');
            $evento_nome = $eventos && !is_wp_error($eventos) ? implode(', ', wp_list_pluck($eventos, 'name')) : '—';

            $url_resultados = admin_url('admin.php?page=votacoes_resultados_visualizar&votacao_id=' . $id);

            echo '<tr>';
            echo '<td>' . get_the_title() . '</td>';
            echo '<td>' . esc_html($ano_vot) . '</td>';
            echo '<td>' . esc_html($evento_nome) . '</td>';
            echo '<td>' . esc_html($status) . '</td>';
            echo '<td>' . esc_html($qtd_votos) . '</td>';
            echo '<td><a href="' . esc_url($url_resultados) . '" class="button">Visualizar Resultados</a></td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';

        // Paginação embaixo também
        if ($pagination_links) {
            echo '<div class="tablenav"><div class="tablenav-pages">' . $pagination_links . '</div></div>';
        }

    } else {
        echo '<p>Nenhuma votação encontrada.</p>';
    }

    echo '</div>';

    wp_reset_postdata();
}

/**
 * Obtém os anos das votações para filtro
 */
function vs_get_votacoes_years() {
    global $wpdb;
    
    $years = $wpdb->get_col(
        "SELECT DISTINCT meta_value 
         FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
         WHERE pm.meta_key = '_vs_ano' 
         AND p.post_type = 'votacoes'
         AND p.post_status = 'publish'
         ORDER BY meta_value DESC"
    );
    
    return array_filter($years);
}
<?php
/**
 * Página administrativa de visualização detalhada dos resultados de uma votação
 * 
 * @package VotingSystem\Admin\Pages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renderiza a página de detalhes dos resultados
 */
function vs_render_results_detail_page() {
    // Checagem de permissões
    if (!current_user_can('manage_options')) {
        wp_die('Você não tem permissão para acessar esta página.');
    }

    // Recebe o ID da votação via URL e valida
    $votacao_id = isset($_GET['votacao_id']) ? intval($_GET['votacao_id']) : 0;

    if ($votacao_id <= 0) {
        echo '<div class="notice notice-error"><p>ID de votação inválido.</p></div>';
        return;
    }

    // Verifica qual subpágina exibir
    $subpage = isset($_GET['subpage']) ? sanitize_text_field($_GET['subpage']) : 'todos';

    if ($subpage === 'unificacao') {
        vs_render_unificacao_page($votacao_id);
    } else {
        vs_render_todos_resultados_page($votacao_id);
    }
}

/**
 * Renderiza a página "Todos os Resultados"
 */
function vs_render_todos_resultados_page($votacao_id) {
    // Define as opções válidas para itens por página
    $opcoes_por_pagina = [30, 50, 100, 200, 500, 1000];

    // Obtém a página atual e o número de itens por página com validação
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $usuarios_por_pagina = isset($_GET['per_page']) && in_array(intval($_GET['per_page']), $opcoes_por_pagina) ? intval($_GET['per_page']) : 30;

    // Recebe os parâmetros de ordenação via URL
    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'user_id';
    $order = isset($_GET['order']) && in_array($_GET['order'], ['asc', 'desc']) ? $_GET['order'] : 'asc';

    // Recupera os IDs dos usuários que votaram na votação
    $voting_user_ids = get_post_meta($votacao_id, '_vs_votantes', true);
    $voting_user_ids = is_array($voting_user_ids) ? $voting_user_ids : [];

    // Calcula total de usuários e páginas para paginação
    $total_users = count($voting_user_ids);
    $total_pages = max(1, ceil($total_users / $usuarios_por_pagina));

    // Ajusta paginação para não ultrapassar limite
    if ($paged > $total_pages) $paged = $total_pages;

    // Define o offset e obtém o slice dos IDs para a página atual
    $offset = ($paged - 1) * $usuarios_por_pagina;
    $ids_pagina = array_slice($voting_user_ids, $offset, $usuarios_por_pagina);

    // Monta o array de usuários para exibição com dados básicos
    $voting_users = [];
    foreach ($ids_pagina as $user_id) {
        $user = get_userdata($user_id);
        if ($user) {
            $voting_users[] = [
                'user_id' => $user->ID,
                'user_name' => $user->display_name,
                'user_email' => $user->user_email,
            ];
        }
    }

    // Captura o termo de busca e filtra o array de usuários
    $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

    if ($search_term !== '') {
        $voting_users = array_filter($voting_users, function($user) use ($search_term) {
            return (stripos($user['user_name'], $search_term) !== false) 
                || (stripos($user['user_email'], $search_term) !== false);
        });
        
        $voting_users = array_values($voting_users);
    }

    $base_url = admin_url('edit.php?post_type=votacoes&page=votacoes_resultados_visualizar&votacao_id=' . $votacao_id);
    ?>

    <div class="wrap">
        <a href="<?php echo esc_url(admin_url('edit.php?post_type=votacoes&page=votacoes_resultados')); ?>" style="display: block; width: fit-content;" class="button button-secondary">← Voltar</a>
        <h1 style="display: inline-block;">
            Detalhe de Resultados da Votação #<?php echo esc_html($votacao_id); ?>
            <a href="<?php echo admin_url('admin-post.php?action=export_csv_votacao&export_csv=true&votacao_id=' . $votacao_id); ?>" style="margin-left: 15px;" class="button button-primary">Exportar para CSV</a>
        </h1>

        <?php vs_render_subpage_navigation($votacao_id, 'todos'); ?>

        <form method="GET" style="margin-bottom:20px;">
            <input type="hidden" name="post_type" value="votacoes" />
            <input type="hidden" name="page" value="votacoes_resultados_visualizar" />
            <input type="hidden" name="votacao_id" value="<?php echo esc_attr($votacao_id); ?>" />
            <input type="hidden" name="paged" value="1" />
            <label for="user_search">Buscar usuário:</label>
            <input type="search" id="user_search" name="s" value="<?php echo esc_attr($search_term); ?>" />
            <button type="submit" class="button">Buscar</button>
        </form>

        <?php if (empty($voting_users)) : ?>
            <p>Nenhum voto registrado para esta votação.</p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped users">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column">
                            <a href="<?php echo vs_build_sort_url($base_url, 'user_id', $order); ?>">
                                ID
                                <?php if ($orderby == 'user_id') : ?>
                                    <span class="dashicons dashicons-arrow-<?php echo ($order == 'asc') ? 'up' : 'down'; ?>"></span>
                                <?php else : ?>
                                    <span class="dashicons dashicons-leftright"></span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th scope="col" class="manage-column">
                            <a href="<?php echo vs_build_sort_url($base_url, 'user_name', $order); ?>">
                                Nome
                                <?php if ($orderby == 'user_name') : ?>
                                    <span class="dashicons dashicons-arrow-<?php echo ($order == 'asc') ? 'up' : 'down'; ?>"></span>
                                <?php else : ?>
                                    <span class="dashicons dashicons-leftright"></span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th scope="col" class="manage-column">
                            <a href="<?php echo vs_build_sort_url($base_url, 'user_email', $order); ?>">
                                E-mail
                                <?php if ($orderby == 'user_email') : ?>
                                    <span class="dashicons dashicons-arrow-<?php echo ($order == 'asc') ? 'up' : 'down'; ?>"></span>
                                <?php else : ?>
                                    <span class="dashicons dashicons-leftright"></span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th scope="col" class="manage-column">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($voting_users as $user) : ?>
                        <tr>
                            <td><?php echo esc_html($user['user_id']); ?></td>
                            <td><?php echo esc_html($user['user_name']); ?></td>
                            <td><?php echo esc_html($user['user_email']); ?></td>
                            <td>
                                <button type="button" 
                                        class="button vs-view-user-votes" 
                                        data-user-id="<?php echo esc_attr($user['user_id']); ?>" 
                                        data-votacao-id="<?php echo esc_attr($votacao_id); ?>">
                                    Ver Respostas
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php
            // Paginação
            $pagination_links = paginate_links([
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'current' => $paged,
                'total' => $total_pages,
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
            ]);

            if ($pagination_links) {
                echo '<div class="tablenav"><div class="tablenav-pages">' . $pagination_links . '</div></div>';
            }
            ?>
        <?php endif; ?>
    </div>

    <!-- Modal para exibir respostas do usuário -->
    <div id="vs-user-votes-modal" style="display: none;">
        <div class="vs-modal-content">
            <span class="vs-modal-close">&times;</span>
            <div id="vs-modal-body"></div>
        </div>
    </div>

    <?php
}

/**
 * Renderiza a navegação entre subpáginas
 */
function vs_render_subpage_navigation($votacao_id, $current_page = 'todos') {
    $base_url = admin_url('edit.php?post_type=votacoes&page=votacoes_resultados_visualizar&votacao_id=' . $votacao_id);

    $links = [
        'todos' => [
            'label' => 'Todos os Resultados',
            'url'   => add_query_arg('subpage', 'todos', $base_url),
        ],
        'unificacao' => [
            'label' => 'Unificação de Respostas',
            'url'   => add_query_arg('subpage', 'unificacao', $base_url),
        ],
    ];

    echo '<div class="subsubsub" style="display: block; position: relative; width: 100%; margin-bottom: 30px;">';
    
    foreach ($links as $key => $link) {
        $class = ($current_page === $key) ? 'current' : '';
        echo "<a href='" . esc_url($link['url']) . "' class='{$class}'>{$link['label']}</a>";
        if ($key !== array_key_last($links)) {
            echo ' | ';
        }
    }
    
    echo '</div>';
}

/**
 * Função auxiliar para construir URLs de ordenação
 */
function vs_build_sort_url($base_url, $orderby, $order) {
    $new_order = ($order == 'asc') ? 'desc' : 'asc';
    return vs_build_url($base_url, ['orderby' => $orderby, 'order' => $new_order]);
}

/**
 * Função auxiliar para construir URLs
 */
function vs_build_url($base_url, $params = []) {
    $query = $_GET;
    foreach ($params as $key => $value) {
        $query[$key] = $value;
    }
    return $base_url . '&' . http_build_query($query);
}
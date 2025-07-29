<?php
/**
 * Página administrativa de visualização detalhada dos resultados de uma votação
 * 
 * @package VotingSystem\Admin\Pages
 */

defined( 'ABSPATH' ) || exit;

require_once VS_PLUGIN_PATH . 'templates/admin/template-ranking-card.php';

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

    // Verifica o filtro de status das respostas
    $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : 'active';

    // Recupera os IDs dos usuários que votaram na votação
    $voting_user_ids = get_post_meta($votacao_id, '_vs_votantes', true);
    $voting_user_ids = is_array($voting_user_ids) ? $voting_user_ids : [];

    // Aplica filtro baseado no status selecionado
    if ($status_filter === 'trashed') {
        $voting_user_ids = vs_filter_users_with_trashed_responses($votacao_id, $voting_user_ids);
    } elseif ($status_filter === 'active') {
        $voting_user_ids = vs_filter_users_with_active_responses($votacao_id, $voting_user_ids);
    }
    // Para 'all', não aplica filtro adicional

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
            // Busca o post de resposta para obter informações de status
            $response_post = vs_get_user_response_post($user_id, $votacao_id);
            
            $voting_users[] = [
                'user_id' => $user->ID,
                'user_name' => $user->display_name,
                'user_email' => $user->user_email,
                'response_post_id' => $response_post ? $response_post->ID : null,
                'response_status' => $response_post ? $response_post->post_status : null,
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
            <a href="<?php echo admin_url('admin-post.php?action=export_csv_votacao&export_csv=true&votacao_id=' . $votacao_id); ?>" style="margin-left: 15px;" class="button button-primary">Exportar | CSV</a>
        </h1>

        <?php vs_render_subpage_navigation($votacao_id, 'todos'); ?>

        <div class="results-container">
            <div class="row">
                <div class="column">
                    <?php vs_render_ranking_card($votacao_id, $total_users); ?>
                </div>
            </div>
        </div>

        <div class="user-container">
            <form method="GET" style="margin-bottom:20px;">
                <input type="hidden" name="post_type" value="votacoes" />
                <input type="hidden" name="page" value="votacoes_resultados_visualizar" />
                <input type="hidden" name="votacao_id" value="<?php echo esc_attr($votacao_id); ?>" />
                <input type="hidden" name="paged" value="1" />
                
                <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <div>
                        <label for="user_search">Buscar usuário:</label>
                        <input type="search" id="user_search" name="s" value="<?php echo esc_attr($search_term); ?>" />
                    </div>
                    
                    <div>
                        <label for="status_filter">Filtrar por status:</label>
                        <select id="status_filter" name="status_filter">
                            <option value="all" <?php selected($status_filter, 'all'); ?>>Mostrar todos</option>
                            <option value="active" <?php selected($status_filter, 'active'); ?>>Mostrar ativos</option>
                            <option value="trashed" <?php selected($status_filter, 'trashed'); ?>>Mostrar removidos</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="button">Filtrar</button>
                </div>
                
                <?php if ($status_filter !== 'all') : ?>
                    <div class="notice notice-info" style="margin-top: 10px;">
                        <p><strong>Filtro ativo:</strong> 
                            <?php 
                            switch($status_filter) {
                                case 'active':
                                    echo 'Exibindo apenas respostas ativas (' . count($voting_users) . ' encontradas)';
                                    break;
                                case 'trashed':
                                    echo 'Exibindo apenas respostas removidas (' . count($voting_users) . ' encontradas)';
                                    break;
                            }
                            ?>
                        </p>
                    </div>
                <?php endif; ?>
            </form>
    
            <?php if (empty($voting_users)) : ?>
                <p>
                    <?php 
                    switch($status_filter) {
                        case 'trashed':
                            echo 'Nenhum usuário com respostas na lixeira encontrado.';
                            break;
                        case 'active':
                            echo 'Nenhum usuário com respostas ativas encontrado.';
                            break;
                        default:
                            echo 'Nenhum voto registrado para esta votação.';
                    }
                    ?>
                </p>
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
                            <th scope="col" class="manage-column">Status</th>
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
                                    <?php if ($user['response_status'] === 'trash') : ?>
                                        <span>Na lixeira</span>
                                    <?php else : ?>
                                        <span>Ativa</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ( $user['response_status'] !== 'trash' ) : ?>
                                        <button type="button" class="button button-small vs-view-user-votes" data-user-id="<?php echo esc_attr($user['user_id']); ?>" data-votacao-id="<?php echo esc_attr($votacao_id); ?>">Ver Detalhes</button>
                                    <?php endif; ?>
                                    
                                    <?php if ( $user['response_post_id'] ) : ?>
                                        <?php if ( $user['response_status'] === 'trash' ) : ?>
                                            <a href="<?php echo esc_url( vs_create_post_action_url( admin_url( 'admin-post.php' ), VS_Nonce_Actions::ACTION_RESTORE, $user['response_post_id'], [ 'votacao_id' => $votacao_id ] ) ); ?>" class="button button-small" style="color: #46b450;">Restaurar</a>

                                            <a href="<?php echo esc_url( vs_create_post_action_url( admin_url( 'admin-post.php' ), VS_Nonce_Actions::ACTION_DELETE, $user['response_post_id'], [ 'votacao_id' => $votacao_id ] ) ); ?>" class="button button-small" style="color: #dc3232;" onclick="return confirm('Tem certeza que deseja remover esta resposta permanentemente? Esta ação não pode ser desfeita.')">Remover</a>
                                        <?php else : ?>
                                            <a href="<?php echo esc_url( vs_create_post_action_url( admin_url( 'admin-post.php' ), VS_Nonce_Actions::ACTION_TRASH, $user['response_post_id'], [ 'votacao_id' => $votacao_id ] ) ); ?>" class="button button-small" style="color: #dc3232;" onclick="return confirm('Tem certeza que deseja remover esta resposta?')">Remover</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
    
                <?php
                /**
                 * Renderiza a paginação
                 */
                function vs_render_pagination($current_page, $total_pages, $base_url, $params = []) {
                    if ($total_pages <= 1) return;
                    
                    echo '<div class="tablenav bottom">';
                    echo '<div class="tablenav-pages">';
                    
                    $page_links = paginate_links([
                        'base' => add_query_arg('paged', '%#%', $base_url),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $current_page,
                        'add_args' => $params
                    ]);
                    
                    if ($page_links) {
                        echo '<span class="pagination-links">' . $page_links . '</span>';
                    }
                    
                    echo '</div>';
                    echo '</div>';
                }
                ?>

                <div style="margin-top: 20px;">
                    <label for="per_page">Itens por página:</label>
                    <select id="per_page" name="per_page" onchange="location.href='<?php echo esc_url($base_url); ?>&per_page=' + this.value + '&orderby=<?php echo esc_attr($orderby); ?>&order=<?php echo esc_attr($order); ?>&s=<?php echo esc_attr($search_term); ?>&status_filter=<?php echo esc_attr($status_filter); ?>'">
                        <?php foreach ($opcoes_por_pagina as $opcao) : ?>
                            <option value="<?php echo $opcao; ?>" <?php selected($usuarios_por_pagina, $opcao); ?>><?php echo $opcao; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>

    </div>



    <style>
    .results-container {
        margin: 20px 0;
    }
    
    .row {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .column {
        flex: 1;
    }
    </style>
    <?php
}

/**
 * Filtra usuários que possuem respostas na lixeira
 */
function vs_filter_users_with_trashed_responses($votacao_id, $user_ids) {
    $users_with_trashed = [];
    
    foreach ($user_ids as $user_id) {
        $args = [
            'post_type' => 'votacao_resposta',
            'author' => $user_id,
            'meta_query' => [
                [
                    'key' => 'vs_votacao_id',
                    'value' => $votacao_id,
                    'compare' => '=',
                ],
            ],
            'posts_per_page' => 1,
            'post_status' => 'trash',
            'fields' => 'ids',
        ];
        
        $trashed_posts = get_posts($args);
        if (!empty($trashed_posts)) {
            $users_with_trashed[] = $user_id;
        }
    }
    
    return $users_with_trashed;
}

/**
 * Filtra usuários que possuem respostas ativas (não na lixeira)
 */
function vs_filter_users_with_active_responses($votacao_id, $user_ids) {
    $users_with_active = [];
    
    foreach ($user_ids as $user_id) {
        $args = [
            'post_type' => 'votacao_resposta',
            'author' => $user_id,
            'meta_query' => [
                [
                    'key' => 'vs_votacao_id',
                    'value' => $votacao_id,
                    'compare' => '=',
                ],
            ],
            'posts_per_page' => 1,
            'post_status' => 'any',
            'fields' => 'ids',
        ];
        
        $posts = get_posts($args);
        if (!empty($posts)) {
            // Verifica se o post não está na lixeira
            $post_status = get_post_status($posts[0]);
            if ($post_status !== 'trash') {
                $users_with_active[] = $user_id;
            }
        }
    }
    
    return $users_with_active;
}

/**
 * Obtém o post de resposta de um usuário para uma votação
 */
function vs_get_user_response_post($user_id, $votacao_id) {
    $args = [
        'post_type' => 'votacao_resposta',
        'author' => $user_id,
        'meta_query' => [
            [
                'key' => 'vs_votacao_id',
                'value' => $votacao_id,
                'compare' => '=',
            ],
        ],
        'posts_per_page' => 1,
        'post_status' => ['publish', 'private', 'trash'],
    ];
    
    $posts = get_posts($args);
    return !empty($posts) ? $posts[0] : null;
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

    echo '<div class="subsubsub" style="display: block; position: relative; width: 100%; margin-bottom: 30px; float: unset !important;">';
    
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

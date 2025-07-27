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
                    <?php vs_render_statistics_card($votacao_id, $total_users); ?>
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
                                    <div style="display: flex; gap: 10px; align-items: center;">
                                        <button type="button" 
                                                class="button vs-view-user-votes" 
                                                data-user-id="<?php echo esc_attr($user['user_id']); ?>" 
                                                data-votacao-id="<?php echo esc_attr($votacao_id); ?>"
                                                <?php echo ($user['response_status'] === 'trash') ? 'disabled title="Resposta na lixeira"' : ''; ?>>
                                            Ver respostas
                                        </button>
                                        
                                        <?php if ($user['response_post_id']) : ?>
                                            <?php if ($user['response_status'] === 'trash') : ?>
                                                <!-- Ação de Restaurar -->
                                                <span class="untrash">
                                                    <a href="<?php echo wp_nonce_url(admin_url('post.php?post=' . $user['response_post_id'] . '&action=untrash'), 'untrash-post_' . $user['response_post_id']); ?>" 
                                                       aria-label="Restaurar resposta do usuário <?php echo esc_attr($user['user_name']); ?> da lixeira">
                                                        Restaurar
                                                    </a>
                                                </span>
                                            <?php else : ?>
                                                <!-- Ação de Mover para lixeira -->
                                                <div id="delete-action">
                                                    <a class="submitdelete deletion" 
                                                       style="color: #d63638;"
                                                       href="<?php echo wp_nonce_url(admin_url('post.php?post=' . $user['response_post_id'] . '&action=trash'), 'trash-post_' . $user['response_post_id']); ?>"
                                                       onclick="return confirm('Tem certeza que deseja mover esta resposta para a lixeira?');">
                                                        Mover para lixeira
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
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

/**
 * Obtém estatísticas das respostas mais votadas para uma votação
 */
function vs_get_voting_statistics($votacao_id, $question_filter = 'all', $group_mode = 'by_answer') {
    $perguntas = vs_get_voting_questions($votacao_id);
    
    if (empty($perguntas)) {
        return [];
    }

    // Busca todas as respostas da votação
    $args = [
        'post_type' => 'votacao_resposta',
        'posts_per_page' => -1,
        'post_status' => ['publish', 'private'],
        'meta_query' => [
            [
                'key' => 'vs_votacao_id',
                'value' => $votacao_id,
                'compare' => '=',
            ],
        ],
    ];

    $response_posts = get_posts($args);
    $statistics = [];

    foreach ($response_posts as $post) {
        $respostas_detalhadas = get_post_meta($post->ID, 'vs_respostas_detalhadas', true);
        $respostas_unificadas = get_post_meta($post->ID, 'vs_resposta_unificada', true);
        
        if (!is_array($respostas_detalhadas)) continue;
        if (!is_array($respostas_unificadas)) $respostas_unificadas = [];

        foreach ($respostas_detalhadas as $index => $resposta_original) {
            // Verifica se deve filtrar por pergunta específica
            if ($question_filter !== 'all' && $question_filter !== "q{$index}") {
                continue;
            }

            // Verifica se existe resposta unificada para esta pergunta
            $tem_resposta_unificada = isset($respostas_unificadas[$index]) && !empty(trim($respostas_unificadas[$index]));
            
            // Usa resposta unificada se existir, senão usa a original
            $resposta_final = $tem_resposta_unificada
                ? $respostas_unificadas[$index]
                : $resposta_original;

            // Formata a resposta (pode ser array ou string)
            $resposta_formatada = is_array($resposta_final) 
                ? implode(', ', array_map('sanitize_text_field', $resposta_final))
                : sanitize_text_field($resposta_final);

            // Ignora respostas vazias
            if (empty(trim($resposta_formatada))) continue;

            // Cria chave única para a estatística baseada no modo de agrupamento
            if ($group_mode === 'by_answer') {
                // Por resposta: agrupa apenas pelo conteúdo da resposta
                $key = $resposta_formatada;
            } else {
                // Por pergunta: agrupa por pergunta + resposta (comportamento original)
                $key = $index . '|' . $resposta_formatada;
            }

            if (!isset($statistics[$key])) {
                $statistics[$key] = [
                    'question_index' => $index,
                    'question_label' => isset($perguntas[$index]['label']) 
                        ? $perguntas[$index]['label'] 
                        : 'Pergunta #' . ($index + 1),
                    'answer' => $resposta_formatada,
                    'count' => 0,
                    'questions' => [], // Para modo "por resposta", armazena quais perguntas têm essa resposta
                    'is_unified' => false, // Inicializa como false
                    'unified_count' => 0, // Conta quantas respostas unificadas contribuem para esta estatística
                ];
            }

            $statistics[$key]['count']++;
            
            // Se esta resposta específica foi unificada, incrementa o contador
            if ($tem_resposta_unificada) {
                $statistics[$key]['unified_count']++;
            }
            
            // Se for agrupamento por resposta, adiciona a pergunta à lista
            if ($group_mode === 'by_answer') {
                if (!in_array($index, $statistics[$key]['questions'])) {
                    $statistics[$key]['questions'][] = $index;
                }
            }
        }
    }

    // Determina se cada estatística deve ser considerada "unificada"
    foreach ($statistics as $key => &$stat) {
        // Uma resposta é considerada "unificada" se pelo menos uma das respostas que contribuem para ela foi unificada
        $stat['is_unified'] = $stat['unified_count'] > 0;
    }

    // Ordena por quantidade de votos (decrescente)
    uasort($statistics, function($a, $b) {
        return $b['count'] - $a['count'];
    });

    return array_values($statistics);
}

/**
 * Renderiza o card de estatísticas dinâmico
 */
function vs_render_statistics_card($votacao_id, $total_users) {
    $perguntas = vs_get_voting_questions($votacao_id);
    $question_filter = isset($_GET['question_filter']) ? sanitize_text_field($_GET['question_filter']) : 'all';
    $group_mode = isset($_GET['group_mode']) ? sanitize_text_field($_GET['group_mode']) : 'by_answer';
    
    // Valida o modo de agrupamento
    if (!in_array($group_mode, ['by_question', 'by_answer'])) {
        $group_mode = 'by_answer';
    }
    
    $statistics = vs_get_voting_statistics($votacao_id, $question_filter, $group_mode);
    
    // Limita a 5 resultados para exibição inicial
    $top_statistics = array_slice($statistics, 0, 5);
    
    // URL para exportação CSV
    $export_url = admin_url('admin-post.php') . '?' . http_build_query([
        'action' => 'export_csv_statistics',
        'votacao_id' => $votacao_id,
        'group_mode' => $group_mode,
        'question_filter' => $question_filter
    ]);
    ?>
    <div class="statistics-card">
        <div class="card-header">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                <div>
                    <p><strong>Votos totais:</strong> <?php echo esc_html($total_users); ?></p>
                    <h2>Mais votados</h2>
                </div>
                <div>
                    <a href="<?php echo esc_url($export_url); ?>" class="button button-secondary" style="font-size: 12px; padding: 4px 8px;">
                        <?php if ($group_mode === 'by_question') : ?>
                            Exportar por pergunta | CSV
                        <?php else : ?>
                            Exportar por resposta | CSV
                        <?php endif; ?>
                    </a>
                </div>
            </div>
            <p>Respostas mais recorrentes de:</p>

            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                <div>
                    <label for="question_filter" class="screen-reader-text">Filtrar por pergunta</label>
                    <select id="question_filter" name="question_filter" class="question-filter">
                        <option value="all" <?php selected($question_filter, 'all'); ?>>Todas as perguntas</option>
                        <?php foreach ($perguntas as $index => $pergunta) : ?>
                            <option value="q<?php echo $index; ?>" <?php selected($question_filter, "q{$index}"); ?>>
                                <?php echo esc_html($pergunta['label'] ?? "Pergunta #" . ($index + 1)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($question_filter === 'all') : ?>
                    <div id="group-mode-container">
                        <fieldset>
                            <legend style="font-size: 12px; margin-bottom: 5px;">Modo de agrupamento:</legend>
                            <div style="display: flex; gap: 15px;">
                                <label style="display: flex; align-items: center; gap: 5px; font-size: 12px;">
                                    <input type="radio" name="group_mode" value="by_answer" <?php checked($group_mode, 'by_answer'); ?>>
                                    Por resposta
                                </label>
                                <label style="display: flex; align-items: center; gap: 5px; font-size: 12px;">
                                    <input type="radio" name="group_mode" value="by_question" <?php checked($group_mode, 'by_question'); ?>>
                                    Por pergunta
                                </label>
                            </div>
                        </fieldset>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($question_filter === 'all') : ?>
                <div style="font-size: 11px; color: #666; margin-top: 5px;">
                    <strong>Por resposta:</strong> considera respostas iguais independentemente da pergunta.<br>
                    <strong>Por pergunta:</strong> considera respostas iguais apenas dentro da mesma pergunta.
                </div>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <?php if (empty($top_statistics)) : ?>
                <p><em>Nenhuma resposta encontrada para os filtros selecionados.</em></p>
            <?php else : ?>
                <table class="vote-results-table">
                    <thead>
                        <tr>
                            <th>Resposta</th>
                            <th>Votos</th>
                            <?php if ($question_filter === 'all' && $group_mode === 'by_question') : ?>
                                <th>Pergunta</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_statistics as $stat) : ?>
                            <tr>
                                <td title="<?php echo esc_attr($stat['answer']); ?>">
                                    <?php 
                                    // Limita o texto para não quebrar o layout
                                    $answer_display = strlen($stat['answer']) > 50 
                                        ? substr($stat['answer'], 0, 47) . '...' 
                                        : $stat['answer'];
                                    echo esc_html($answer_display); 
                                    ?>
                                </td>
                                <td><strong><?php echo esc_html($stat['count']); ?></strong></td>
                                <?php if ($question_filter === 'all' && $group_mode === 'by_question') : ?>
                                    <td>
                                        <small><?php echo esc_html($stat['question_label']); ?></small>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php if (count($statistics) > 5) : ?>
                        <tfoot>
                            <tr>
                                <td colspan="<?php echo ($question_filter === 'all' && $group_mode === 'by_question') ? '3' : '2'; ?>">
                                    <a href="#" id="vs-show-all-results" data-votacao-id="<?php echo esc_attr($votacao_id); ?>" data-question-filter="<?php echo esc_attr($question_filter); ?>" data-group-mode="<?php echo esc_attr($group_mode); ?>">
                                        Ver todos os votos
                                    </a>
                                </td>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            <?php endif; ?>
        </div>

        <div class="card-footer">
            <?php if ($question_filter !== 'all') : ?>
                <p><small>
                    <strong>Filtro ativo:</strong> 
                    <?php 
                    $pergunta_selecionada = null;
                    foreach ($perguntas as $index => $pergunta) {
                        if ("q{$index}" === $question_filter) {
                            $pergunta_selecionada = $pergunta['label'] ?? "Pergunta #" . ($index + 1);
                            break;
                        }
                    }
                    echo esc_html($pergunta_selecionada);
                    ?>
                </small></p>
            <?php else : ?>
                <p><small>
                    <strong>Modo ativo:</strong> 
                    <?php if ($group_mode === 'by_answer') : ?>
                        Agrupamento por resposta - respostas iguais de diferentes perguntas são unificadas.
                    <?php else : ?>
                        Agrupamento por pergunta - respostas iguais apenas dentro da mesma pergunta.
                    <?php endif; ?>
                </small></p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Filtro por pergunta
        $('#question_filter').on('change', function() {
            var currentUrl = new URL(window.location.href);
            var selectedValue = $(this).val();
            
            if (selectedValue === 'all') {
                currentUrl.searchParams.delete('question_filter');
            } else {
                currentUrl.searchParams.set('question_filter', selectedValue);
                // Remove o group_mode quando uma pergunta específica é selecionada
                currentUrl.searchParams.delete('group_mode');
            }
            
            window.location.href = currentUrl.toString();
        });

        // Radio buttons de modo de agrupamento
        $('input[name="group_mode"]').on('change', function() {
            var currentUrl = new URL(window.location.href);
            var selectedValue = $(this).val();
            
            currentUrl.searchParams.set('group_mode', selectedValue);
            window.location.href = currentUrl.toString();
        });

        // Modal para ver lista completa
        $('#vs-show-all-results').on('click', function(e) {
            e.preventDefault();
            
            var votacaoId = $(this).data('votacao-id');
            var questionFilter = $(this).data('question-filter');
            var groupMode = $(this).data('group-mode');
            
            // Aqui você pode implementar um modal ou redirecionar para uma página com todos os resultados
            // Por enquanto, vamos mostrar um alert
            alert('Funcionalidade de lista completa será implementada em breve.');
        });
    });
    </script>
    <?php
}
<?php
/**
 * Página administrativa de unificação de respostas
 * 
 * @package VotingSystem\Admin\Pages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renderiza a página de unificação de respostas
 */
function vs_render_unificacao_page($votacao_id) {
    // Checagem de permissões
    if (!current_user_can('manage_options')) {
        wp_die('Você não tem permissão para acessar esta página.');
    }

    // Carrega configuração das perguntas para esta votação
    $questions = get_post_meta($votacao_id, 'vs_questions', true);
    if (!is_array($questions)) {
        $questions = array();
    }

    // Configuração de paginação e busca
    $options_por_pagina = [20, 50, 100, 200];
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = isset($_GET['per_page']) && in_array(intval($_GET['per_page']), $options_por_pagina) ? intval($_GET['per_page']) : 50;
    
    // Parâmetros de ordenação - NOVO
    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'user_id';
    $order = isset($_GET['order']) && in_array(sanitize_text_field($_GET['order']), ['asc', 'desc']) ? sanitize_text_field($_GET['order']) : 'asc';
    
    // Parâmetro de busca
    $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

    // Query para agregação (todos os registros) - mantém funcionalidade da Coluna 2
    $args_all = array(
        'post_type'      => 'votacao_resposta',
        'posts_per_page' => -1,
        'post_status'    => array('publish', 'private'),
        'meta_query'     => array(
            array(
                'key'     => 'vs_votacao_id',
                'value'   => $votacao_id,
                'compare' => '=',
            ),
        ),
        'orderby' => 'ID',
        'order'   => 'ASC',
    );
    $query_all = new WP_Query($args_all);

    // Query paginada para a tabela com busca
    $args_paged = array(
        'post_type'      => 'votacao_resposta',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'post_status'    => array('publish', 'private'),
        'meta_query'     => array(
            array(
                'key'     => 'vs_votacao_id',
                'value'   => $votacao_id,
                'compare' => '=',
            ),
        ),
        'orderby' => 'ID',
        'order'   => 'ASC',
    );
    
    // Adiciona busca se houver termo
    if (!empty($search_term)) {
        // Buscar usuários que correspondem ao termo de busca
        $user_search = new WP_User_Query(array(
            'search' => '*' . $search_term . '*',
            'search_columns' => array('user_email', 'display_name', 'user_login'),
            'fields' => 'ID'
        ));
        $matching_user_ids = $user_search->get_results();
        
        // Buscar por ID de usuário se o termo for numérico
        if (is_numeric($search_term)) {
            $matching_user_ids[] = intval($search_term);
        }
        
        // Buscar nas perguntas configuradas
        $matching_question_indices = array();
        foreach ($questions as $idx => $question) {
            if (isset($question['label']) && stripos($question['label'], $search_term) !== false) {
                $matching_question_indices[] = $idx;
            }
        }
        
        // Construir meta_query com busca em múltiplos campos
        $search_meta_query = array('relation' => 'OR');
        
        // Busca nas respostas detalhadas
        $search_meta_query[] = array(
            'key'     => 'vs_respostas_detalhadas',
            'value'   => $search_term,
            'compare' => 'LIKE'
        );
        
        // Busca nas respostas unificadas
        $search_meta_query[] = array(
            'key'     => 'vs_resposta_unificada',
            'value'   => $search_term,
            'compare' => 'LIKE'
        );
        
        // Busca por usuários correspondentes
        if (!empty($matching_user_ids)) {
            $search_meta_query[] = array(
                'key'     => 'vs_usuario_id',
                'value'   => $matching_user_ids,
                'compare' => 'IN'
            );
        }
        
        // Se encontrou perguntas correspondentes, incluir posts que tenham respostas para essas perguntas
        if (!empty($matching_question_indices)) {
            foreach ($matching_question_indices as $question_idx) {
                $search_meta_query[] = array(
                    'key'     => 'vs_respostas_detalhadas',
                    'value'   => '"' . $question_idx . '"',
                    'compare' => 'LIKE'
                );
            }
        }
        
        $args_paged['meta_query'][] = $search_meta_query;
    }
    
    $query_paged = new WP_Query($args_paged);

    // Variáveis de paginação
    $total_posts = $query_paged->found_posts;
    $total_pages = $query_paged->max_num_pages;
    $base_url = admin_url('edit.php?post_type=votacoes&page=votacoes_resultados_visualizar&votacao_id=' . $votacao_id . '&subpage=unificacao');

    // Calcular usuários únicos e total de respostas
    $unique_users = array();
    $total_responses = 0;
    
    if ($query_all->have_posts()) {
        while ($query_all->have_posts()) {
            $query_all->the_post();
            $resp_post_id = get_the_ID();
            
            // Verificação adicional de segurança: pula posts que estão na lixeira
            $post_status = get_post_status($resp_post_id);
            if ($post_status === 'trash') {
                continue;
            }
            
            // Coleta usuário único
            $user_id = get_post_meta($resp_post_id, 'vs_usuario_id', true);
            if ($user_id) {
                $unique_users[$user_id] = true;
            }
            
            // Conta respostas detalhadas
            $respostas_detalhadas = get_post_meta($resp_post_id, 'vs_respostas_detalhadas', true);
            if (is_array($respostas_detalhadas)) {
                $total_responses += count($respostas_detalhadas);
            }
        }
        wp_reset_postdata();
    }
    
    $total_unique_users = count($unique_users);
    
    // Calcular usuários e respostas da página atual
    $current_page_users = array();
    $current_page_responses = 0;
    
    if ($query_paged->have_posts()) {
        while ($query_paged->have_posts()) {
            $query_paged->the_post();
            $resp_post_id = get_the_ID();
            
            $post_status = get_post_status($resp_post_id);
            if ($post_status === 'trash') {
                continue;
            }
            
            $user_id = get_post_meta($resp_post_id, 'vs_usuario_id', true);
            if ($user_id) {
                $current_page_users[$user_id] = true;
            }
            
            $respostas_detalhadas = get_post_meta($resp_post_id, 'vs_respostas_detalhadas', true);
            if (is_array($respostas_detalhadas)) {
                $current_page_responses += count($respostas_detalhadas);
            }
        }
        wp_reset_postdata();
    }
    
    $current_unique_users = count($current_page_users);

    // Constrói agregação para Coluna 2 baseada nas unificações armazenadas por resposta
    // Agora só considera respostas que não estão na lixeira
    $agg_counts = array();
    $agg_posts = array();
    $agg_slot_map = array();

    if ($query_all->have_posts()) {
        while ($query_all->have_posts()) {
            $query_all->the_post();

            $resp_post_id = get_the_ID();
            
            // Verificação adicional de segurança: pula posts que estão na lixeira
            $post_status = get_post_status($resp_post_id);
            if ($post_status === 'trash') {
                continue;
            }

            // Cada post de resposta pode ter um array de valores unificados por pergunta
            $unifications = get_post_meta($resp_post_id, 'vs_resposta_unificada', true);
            if (!is_array($unifications)) {
                $unifications = array();
            }

            // Carrega respostas detalhadas para saber quais índices de pergunta existem
            $respostas_detalhadas = get_post_meta($resp_post_id, 'vs_respostas_detalhadas', true);
            if (!is_array($respostas_detalhadas)) {
                $respostas_detalhadas = array();
            }

            // Percorre os índices respondidos; se existe unificação para esse índice, agrega
            foreach ($respostas_detalhadas as $idx => $unused_value) {
                $unification_value = isset($unifications[$idx]) ? (string) $unifications[$idx] : '';

                // Pula vazios (slots ainda não unificados)
                if ('' === trim($unification_value)) {
                    continue;
                }

                if (!isset($agg_counts[$unification_value])) {
                    $agg_counts[$unification_value] = 0;
                    $agg_posts[$unification_value] = array();
                    $agg_slot_map[$unification_value] = array();
                }

                $agg_counts[$unification_value]++;
                $agg_posts[$unification_value][] = $resp_post_id;
                $agg_slot_map[$unification_value][] = array(
                    'post_id' => $resp_post_id,
                    'index'   => $idx,
                );
            }
        }
        wp_reset_postdata();
    }

    // Remove duplicatas das listas de posts
    foreach ($agg_posts as $k => $ids) {
        $agg_posts[$k] = array_values(array_unique($ids));
    }

    // Constrói lista filtrada (apenas chaves não vazias) para saída da Coluna 2
    $agg_non_empty = array();
    foreach ($agg_counts as $k => $cnt) {
        if ('' !== trim($k)) {
            $agg_non_empty[$k] = $cnt;
        }
    }

    ?>
    <div class="wrap">
        <a href="<?php echo esc_url(admin_url('edit.php?post_type=votacoes&page=votacoes_resultados')); ?>" class="button button-secondary" style="width: fit-content;">← Voltar</a>
        <h1>
            Detalhe de Resultados da Votação #<?php echo absint($votacao_id); ?>
            <a href="<?php echo esc_url(admin_url('admin-post.php?action=export_csv_votacao&export_csv=true&votacao_id=' . $votacao_id)); ?>" class="button button-primary" style="margin-left: 15px;">Exportar para CSV</a>
        </h1>

        <?php vs_render_subpage_navigation($votacao_id, 'unificacao'); ?>

        <div class="unificacao-container">
            <!-- Coluna 1: Todos os slots de resposta -->
            <div class="unificacao-coluna" id="respostas-coluna">
                <h2 class="unificacao-title">Respostas Votação: <?php echo esc_html(get_the_title($votacao_id)); ?></h2>

                <!-- Campo de busca e controles superiores - SEMPRE VISÍVEL -->
                <div class="unificacao-search-controls" style="margin-bottom: 15px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                    <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                        <!-- Campo de busca -->
                        <div style="display: flex; align-items: center; gap: 3px;">
                            <label for="search_unificacao">Buscar:</label>
                            <input type="search" 
                                   id="search_unificacao" 
                                   name="s" 
                                   value="<?php echo esc_attr($search_term); ?>" 
                                   placeholder="Buscar em usuários, perguntas, respostas e unificações..."
                                   style="width: 300px; padding: 6px 10px;" />
                            <button type="button" 
                                    class="button" 
                                    onclick="performSearch()"
                                    style="padding: 6px 12px;">
                                Buscar
                            </button>
                            <?php if (!empty($search_term)) : ?>
                                <a href="<?php echo esc_url($base_url); ?>" 
                                   class="button" 
                                   style="padding: 6px 12px;">
                                    ✕ Limpar
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Seletor de itens por página -->
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <label for="per_page_unificacao">Itens por página:</label>
                            <select id="per_page_unificacao" name="per_page" onchange="changePerPage(this.value)">
                                <?php foreach ($options_por_pagina as $option) : ?>
                                    <option value="<?php echo $option; ?>" <?php selected($per_page, $option); ?>><?php echo $option; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Informações de resultados -->
                        <div style="color: #666; font-size: 13px;">
                            <?php if (!empty($search_term)) : ?>
                                <strong>Busca por:</strong> "<?php echo esc_html($search_term); ?>" | 
                            <?php endif; ?>
                            <?php echo $current_unique_users; ?> de <?php echo $total_unique_users; ?> usuários | 
                            <?php echo $current_page_responses; ?> de <?php echo $total_responses; ?> respostas
                            <?php if (!$query_paged->have_posts() && !empty($search_term)) : ?>
                                <br><span style="color: #d63638; font-weight: bold;">Nenhum resultado encontrado para esta busca</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if (!$query_paged->have_posts()) : ?>
                    <?php if (!empty($search_term)) : ?>
                        <div class="vs-notice vs-notice-warning" style="margin: 15px 0;">
                            <p><strong>Nenhum resultado encontrado</strong></p>
                            <p>Sua busca por "<strong><?php echo esc_html($search_term); ?></strong>" não retornou resultados.</p>
                            <p>A busca procura em:</p>
                            <ul style="margin-left: 20px;">
                                <li><strong>Usuários:</strong> email, nome de exibição, login ou ID</li>
                                <li><strong>Perguntas:</strong> texto das perguntas configuradas</li>
                                <li><strong>Respostas:</strong> conteúdo das respostas dos usuários</li>
                                <li><strong>Respostas Unificadas:</strong> texto das unificações</li>
                            </ul>
                            <p>Tente:</p>
                            <ul style="margin-left: 20px;">
                                <li>Verificar a ortografia</li>
                                <li>Usar termos mais gerais</li>
                                <li>Buscar por email do usuário ou ID</li>
                                <li>Usar palavras-chave das perguntas</li>
                                <li><a href="<?php echo esc_url($base_url); ?>">Limpar a busca</a> para ver todos os resultados</li>
                            </ul>
                        </div>
                    <?php else : ?>
                        <p>Nenhuma resposta encontrada para esta votação.</p>
                    <?php endif; ?>
                <?php else : ?>
        

                    <!-- Paginação superior -->
                    <?php if ($total_pages > 1) : ?>
                        <div class="pagination-wrapper" style="margin-bottom: 15px;">
                            <div class="pagination-info">
                                Exibindo <?php echo count($query_paged->posts); ?> de <?php echo $total_posts; ?> resultados 
                                (página <?php echo $paged; ?> de <?php echo $total_pages; ?>)
                            </div>
                            <div class="pagination-controls">
                                <?php
                                // Botão Anterior
                                if ($paged > 1) {
                                    $prev_url = add_query_arg(['paged' => $paged - 1, 'per_page' => $per_page, 's' => $search_term], $base_url);
                                    echo '<a href="' . esc_url($prev_url) . '" class="button pagination-btn">« Anterior</a>';
                                }
                                
                                // Botões numerados
                                $start_page = max(1, $paged - 2);
                                $end_page = min($total_pages, $paged + 2);
                                
                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    $page_url = add_query_arg(['paged' => $i, 'per_page' => $per_page, 's' => $search_term], $base_url);
                                    $class = ($i === $paged) ? 'button pagination-btn button-primary' : 'button pagination-btn';
                                    echo '<a href="' . esc_url($page_url) . '" class="' . $class . '">' . $i . '</a>';
                                }
                                
                                // Botão Próxima
                                if ($paged < $total_pages) {
                                    $next_url = add_query_arg(['paged' => $paged + 1, 'per_page' => $per_page, 's' => $search_term], $base_url);
                                    echo '<a href="' . esc_url($next_url) . '" class="button pagination-btn">Próxima »</a>';
                                }
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <button type="button" class="button-unificacao" id="btn-unificacao-top" disabled>Unificação</button>
                    <span class="text-unificacao">Selecione uma ou mais respostas para unificar.</span> 
                    
                    <form id="form-unificacao" class="unificacao-form" style="margin-top:10px;">
                        <table class="unificacao-table" id="unificacao-table" style="width:100%;">
                            <thead>
                                <tr>
                                    <th class="unificacao-checkbox-column"><input type="checkbox" id="select-all" /></th>
                                    <th class="unificacao-usuario-column">
                                        <a href="<?php echo vs_build_sort_url($base_url, 'user_id', $order); ?>">
                                            Usuário
                                            <?php if ($orderby == 'user_id') : ?>
                                                <span class="dashicons dashicons-arrow-<?php echo ($order == 'asc') ? 'up' : 'down'; ?>"></span>
                                            <?php else : ?>
                                                <span class="dashicons dashicons-leftright"></span>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th class="unificacao-pergunta-column">
                                        <a href="<?php echo vs_build_sort_url($base_url, 'question_index', $order); ?>">
                                            Pergunta
                                            <?php if ($orderby == 'question_index') : ?>
                                                <span class="dashicons dashicons-arrow-<?php echo ($order == 'asc') ? 'up' : 'down'; ?>"></span>
                                            <?php else : ?>
                                                <span class="dashicons dashicons-leftright"></span>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th class="unificacao-resposta-column">
                                        <a href="<?php echo vs_build_sort_url($base_url, 'response_text', $order); ?>">
                                            Resposta
                                            <?php if ($orderby == 'response_text') : ?>
                                                <span class="dashicons dashicons-arrow-<?php echo ($order == 'asc') ? 'up' : 'down'; ?>"></span>
                                            <?php else : ?>
                                                <span class="dashicons dashicons-leftright"></span>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th class="unificacao-resposta-unificada-column">
                                        <a href="<?php echo vs_build_sort_url($base_url, 'unified_response', $order); ?>">
                                            Resposta Unificada
                                            <?php if ($orderby == 'unified_response') : ?>
                                                <span class="dashicons dashicons-arrow-<?php echo ($order == 'asc') ? 'up' : 'down'; ?>"></span>
                                            <?php else : ?>
                                                <span class="dashicons dashicons-leftright"></span>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Conta total de linhas para informação
                                $total_rows = 0;
                                
                                // Coleta todos os dados para ordenação
                                $table_data = array();
                                
                                while ($query_paged->have_posts()) :
                                    $query_paged->the_post();
                                    $post_id = get_the_ID();

                                    // Verificação adicional de segurança: pula posts que estão na lixeira
                                    $post_status = get_post_status($post_id);
                                    if ($post_status === 'trash') {
                                        continue;
                                    }

                                    // Coleta exibição do usuário (meta armazenado vs_usuario_id)
                                    $user_id = get_post_meta($post_id, 'vs_usuario_id', true);
                                    $user = $user_id ? get_userdata($user_id) : null;

                                    if ($user) {
                                        $usuario_texto = sprintf('#%d %s', $user->ID, $user->user_email);
                                    } else {
                                        $usuario_texto = '&#8212;';
                                    }

                                    $link_edicao = get_edit_post_link($post_id);

                                    // Array de respostas detalhadas (índice => string|array de resposta)
                                    $respostas_detalhadas = get_post_meta($post_id, 'vs_respostas_detalhadas', true);
                                    if (!is_array($respostas_detalhadas)) {
                                        $respostas_detalhadas = array();
                                    }

                                    // Array de valores unificados POR RESPOSTA
                                    $unifications = get_post_meta($post_id, 'vs_resposta_unificada', true);
                                    if (!is_array($unifications)) {
                                        $unifications = array();
                                    }

                                    foreach ($respostas_detalhadas as $idx => $resposta_individual) {
                                        // Label da pergunta da configuração da votação
                                        $question_label = isset($questions[$idx]['label'])
                                            ? $questions[$idx]['label']
                                            : sprintf('Pergunta #%d', ($idx + 1));

                                        // Normaliza texto da resposta
                                        if (is_array($resposta_individual)) {
                                            $resposta_texto = implode(', ', array_map('sanitize_text_field', $resposta_individual));
                                        } else {
                                            $resposta_texto = sanitize_text_field($resposta_individual);
                                        }

                                        // Valor unificado: agora vem do array meta POR RESPOSTA
                                        $unificada_texto = isset($unifications[$idx]) && '' !== trim($unifications[$idx])
                                            ? $unifications[$idx]
                                            : '—';
                                        
                                        // Adiciona os dados ao array para ordenação
                                        $table_data[] = array(
                                            'post_id' => $post_id,
                                            'question_index' => $idx,
                                            'user_id' => $user_id,
                                            'user_email' => $user ? $user->user_email : '',
                                            'usuario_texto' => $usuario_texto,
                                            'question_label' => $question_label,
                                            'resposta_texto' => $resposta_texto,
                                            'unificada_texto' => $unificada_texto,
                                            'link_edicao' => $link_edicao
                                        );
                                    }
                                endwhile;
                                wp_reset_postdata();
                                
                                // Aplica ordenação aos dados coletados
                                if (!empty($table_data)) {
                                    usort($table_data, function($a, $b) use ($orderby, $order) {
                                        $result = 0;
                                        
                                        switch ($orderby) {
                                            case 'user_id':
                                                $result = $a['user_id'] - $b['user_id'];
                                                break;
                                            case 'user_email':
                                                $result = strcasecmp($a['user_email'], $b['user_email']);
                                                break;
                                            case 'question_index':
                                                $result = $a['question_index'] - $b['question_index'];
                                                break;
                                            case 'response_text':
                                                $result = strcasecmp($a['resposta_texto'], $b['resposta_texto']);
                                                break;
                                            case 'unified_response':
                                                $result = strcasecmp($a['unificada_texto'], $b['unificada_texto']);
                                                break;
                                            default:
                                                $result = $a['user_id'] - $b['user_id'];
                                        }
                                        
                                        return ($order === 'desc') ? -$result : $result;
                                    });
                                }
                                
                                // Exibe os dados ordenados
                                foreach ($table_data as $row) :
                                    $total_rows++;
                                    ?>
                                    <tr class="unificacao-tr"
                                        data-post-id="<?php echo esc_attr($row['post_id']); ?>"
                                        data-question-index="<?php echo esc_attr($row['question_index']); ?>"
                                    >
                                        <td class="col-checkbox" style="text-align:center;">
                                            <input type="checkbox" name="respostas_ids[]" value="<?php echo esc_attr($row['post_id']); ?>" />
                                        </td>
                                        <td class="col-user">
                                            <?php if ($row['link_edicao']) : ?>
                                                <a href="<?php echo esc_url($row['link_edicao']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($row['usuario_texto']); ?></a>
                                            <?php else : ?>
                                                <?php echo esc_html($row['usuario_texto']); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="col-question">
                                            <div class="tooltip" title="<?php echo esc_attr($row['question_label']); ?>">
                                                <?php echo esc_html($row['question_label']); ?>
                                                <span class="tooltip-text"><?php echo esc_html($row['question_label']); ?></span>
                                            </div>
                                        </td>
                                        <td class="col-answers unificacao-resposta-column">
                                            <div class="tooltip" title="<?php echo esc_attr($row['resposta_texto']); ?>">
                                                <?php echo esc_html($row['resposta_texto']); ?>
                                                <span class="tooltip-text"><?php echo esc_html($row['resposta_texto']); ?></span>
                                            </div>
                                        </td>
                                        <td class="col-unified-answer unificacao-resposta-unificada-column">
                                            <div class="tooltip" title="<?php echo esc_attr($row['unificada_texto']); ?>">
                                                <?php echo esc_html($row['unificada_texto']); ?>
                                                <span class="tooltip-text"><?php echo esc_html($row['unificada_texto']); ?></span>
                                            </div>
                                            <?php if ($row['unificada_texto'] !== '—') : ?>
                                                <button type="button" class="vs-edit-unified-btn" 
                                                        title="Editar resposta unificada" 
                                                        data-post-id="<?php echo esc_attr($row['post_id']); ?>"
                                                        data-question-index="<?php echo esc_attr($row['question_index']); ?>"
                                                        data-votacao-id="<?php echo esc_attr($votacao_id); ?>">
                                                    <span class="dashicons dashicons-edit"></span>
                                                </button>
                                                <button type="button" class="vs-clear-unified-btn" 
                                                        title="Limpar resposta unificada" 
                                                        data-post-id="<?php echo esc_attr($row['post_id']); ?>"
                                                        data-question-index="<?php echo esc_attr($row['question_index']); ?>"
                                                        data-votacao-id="<?php echo esc_attr($votacao_id); ?>">
                                                    <span class="dashicons dashicons-trash"></span>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php
                                endforeach;
                                ?>
                            </tbody>
                        </table>
                    </form>

                    <?php
                    // Renderiza a paginação inferior por botões
                    if ($total_pages > 1) {
                        echo '<div class="pagination-wrapper" style="margin-top: 15px;">';
                        echo '<div class="pagination-info">';
                        echo 'Exibindo ' . count($query_paged->posts) . ' de ' . $total_posts . ' resultados ';
                        echo '(página ' . $paged . ' de ' . $total_pages . ')';
                        echo '</div>';
                        echo '<div class="pagination-controls">';
                        
                        // Botão Anterior
                        if ($paged > 1) {
                            $prev_url = add_query_arg(['paged' => $paged - 1, 'per_page' => $per_page, 's' => $search_term], $base_url);
                            echo '<a href="' . esc_url($prev_url) . '" class="button pagination-btn">« Anterior</a>';
                        }
                        
                        // Botões numerados
                        $start_page = max(1, $paged - 2);
                        $end_page = min($total_pages, $paged + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            $page_url = add_query_arg(['paged' => $i, 'per_page' => $per_page, 's' => $search_term], $base_url);
                            $class = ($i === $paged) ? 'button pagination-btn button-primary' : 'button pagination-btn';
                            echo '<a href="' . esc_url($page_url) . '" class="' . $class . '">' . $i . '</a>';
                        }
                        
                        // Botão Próxima
                        if ($paged < $total_pages) {
                            $next_url = add_query_arg(['paged' => $paged + 1, 'per_page' => $per_page, 's' => $search_term], $base_url);
                            echo '<a href="' . esc_url($next_url) . '" class="button pagination-btn">Próxima »</a>';
                        }
                        
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>

                    <button type="button" class="button-unificacao" id="btn-unificacao-bottom" style="margin-top: 10px;" disabled >Unificação</button>
                    <span class="text-unificacao">Selecione uma ou mais respostas para unificar.</span>
                <?php endif; ?>
            </div>

            <!-- Coluna 2: Valores unificados agregados (dados armazenados reais) -->
            <div class="unificacao-coluna" id="unificados-coluna">
                <h2 class="unificacao-title">Unificações de Resposta</h2>

                <?php if (empty($agg_non_empty)) : ?>
                    <p>Nenhuma unificação realizada.</p>
                    <style>.unificacao-second-table { opacity: .44; }</style>
                <?php else : ?>
                    <table class="unificacao-second-table" id="unificacao-second-table">
                        <thead>
                            <tr>
                                <th>Resposta Unificada</th>
                                <th>Contagem</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agg_non_empty as $resposta_key => $count) : ?>
                                <?php
                                $resposta_text = $resposta_key;
                                $display_text = (strlen($resposta_text) > 80)
                                    ? substr($resposta_text, 0, 77) . '...'
                                    : $resposta_text;
                                ?>
                                <tr data-resposta-key="<?php echo esc_attr($resposta_key); ?>">
                                    <td>
                                        <div class="tooltip" title="<?php echo esc_attr($resposta_text); ?>">
                                            <?php echo esc_html($display_text); ?>
                                            <span class="tooltip-text"><?php echo esc_html($resposta_text); ?></span>
                                        </div>
                                    </td>
                                    <td style="text-align:center;"><?php echo intval($count); ?></td>
                                    <td>
                                        <a href="#"
                                           class="unificacao-ver-todos"
                                           data-resposta-key="<?php echo esc_attr($resposta_key); ?>">
                                            Ver todos
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="resposta-modal" id="modal-unificacao" style="display:none;"></div>
    <div class="modal-overlay" id="modal-overlay" style="display:none;"></div>

    <?php
    // Marcador oculto para JS ler o ID da votação se necessário
    printf(
        '<div id="vs-votacao-marker" data-votacao-id="%d" style="display:none;"></div>',
        absint($votacao_id)
    );
    ?>

    <script>
    // Funções para busca e paginação
    function performSearch() {
        var searchTerm = document.getElementById('search_unificacao').value;
        var currentUrl = new URL(window.location.href);
        
        if (searchTerm.trim()) {
            currentUrl.searchParams.set('s', searchTerm);
        } else {
            currentUrl.searchParams.delete('s');
        }
        
        // Reset para primeira página ao buscar
        currentUrl.searchParams.set('paged', '1');
        
        window.location.href = currentUrl.toString();
    }
    
    function changePerPage(value) {
        var currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('per_page', value);
        currentUrl.searchParams.set('paged', '1'); // Reset para primeira página
        window.location.href = currentUrl.toString();
    }
    
    // Permitir busca com Enter
    document.getElementById('search_unificacao').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch();
        }
    });

    jQuery(document).ready(function($) {
        // Aguardar um pouco para garantir que os botões estejam carregados
        setTimeout(function() {
            var buttons = $('.vs-edit-unified-btn');
            
            // Adicionar event listener diretamente a cada botão
            buttons.each(function(i) {
                var $btn = $(this);
                
                // Remover event listeners anteriores
                $btn.off('click.editUnified');
                
                // Adicionar novo event listener
                $btn.on('click.editUnified', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var postId = $(this).data('post-id');
                    var questionIndex = $(this).data('question-index');
                    var votacaoId = $(this).data('votacao-id');
                    var currentValue = $(this).closest('td').find('.tooltip').attr('title');
                    
                    // Verificar se já existe um modal aberto
                    if ($('#edit-unified-modal').length > 0) {
                        $('#edit-unified-modal').remove();
                    }
                    
                    // Criar modal de edição
                    var modalContent = '<div style="padding: 20px;">';
                    modalContent += '<h3>Editar Resposta Unificada</h3>';
                    modalContent += '<label for="edit-unified-input">Nova resposta unificada:</label>';
                    modalContent += '<input type="text" id="edit-unified-input" value="' + (currentValue || '') + '" style="width: 100%; padding: 8px; margin: 10px 0;" />';
                    modalContent += '<div style="margin-top: 20px; text-align: right;">';
                    modalContent += '<button type="button" class="button" onclick="closeEditModal()">Cancelar</button>';
                    modalContent += '<button type="button" class="button-primary" id="save-edit-unified" style="margin-left: 10px;">Salvar</button>';
                    modalContent += '</div>';
                    modalContent += '</div>';
                    
                    // Mostrar modal
                    showEditModal(modalContent, postId, questionIndex, votacaoId);
                });
            });
        }, 500);

        // Event listener para botões de limpar
        setTimeout(function() {
            var clearButtons = $('.vs-clear-unified-btn');
            
            clearButtons.each(function(i) {
                var $btn = $(this);
                
                // Remover event listeners anteriores
                $btn.off('click.clearUnified');
                
                // Adicionar novo event listener
                $btn.on('click.clearUnified', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var postId = $(this).data('post-id');
                    var questionIndex = $(this).data('question-index');
                    var votacaoId = $(this).data('votacao-id');
                    
                    var $btn = $(this);
                    var originalHtml = $btn.html();
                    $btn.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation: spin 1s linear infinite;"></span>');
                    
                    // AJAX para limpar
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'vs_update_resposta_unificada',
                            nonce: <?php echo json_encode(wp_create_nonce("vs_unificacao_nonce")); ?>,
                            votacao_id: votacaoId,
                            nova_resposta_unificada: '',
                            clear_operation: 'true',
                            linhas: JSON.stringify([{
                                postId: parseInt(postId),
                                perguntaIndex: parseInt(questionIndex)
                            }])
                        },
                        success: function(response) {
                            if (response.success) {
                                // Recarregar página para mostrar mudanças
                                location.reload();
                            } else {
                                alert('Erro ao limpar: ' + (response.data || 'Erro desconhecido'));
                                $btn.prop('disabled', false).html(originalHtml);
                            }
                        },
                        error: function() {
                            alert('Erro de conexão ao tentar limpar.');
                            $btn.prop('disabled', false).html(originalHtml);
                        }
                    });
                });
            });
        }, 500);
        
        // Função para mostrar modal de edição
        function showEditModal(content, postId, questionIndex, votacaoId) {
            var modal = '<div id="edit-unified-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">';
            modal += '<div style="background: white; border-radius: 8px; max-width: 500px; width: 90%;">';
            modal += content;
            modal += '</div>';
            modal += '</div>';
            
            $('body').append(modal);
            
            // Focar no input após um pequeno delay
            setTimeout(function() {
                $('#edit-unified-input').focus();
            }, 100);
            
            // Event listener para salvar
            $('#save-edit-unified').on('click', function() {
                var newValue = $('#edit-unified-input').val().trim();
                
                if (!newValue) {
                    alert('Por favor, digite uma resposta unificada.');
                    return;
                }
                
                var $btn = $(this);
                var originalText = $btn.text();
                $btn.prop('disabled', true).text('Salvando...');
                
                // AJAX para salvar
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vs_update_resposta_unificada',
                        nonce: <?php echo json_encode(wp_create_nonce("vs_unificacao_nonce")); ?>,
                        votacao_id: votacaoId,
                        nova_resposta_unificada: newValue,
                        linhas: JSON.stringify([{
                            postId: parseInt(postId),
                            perguntaIndex: parseInt(questionIndex)
                        }])
                    },
                    success: function(response) {
                        if (response.success) {
                            // Recarregar página para mostrar mudanças
                            location.reload();
                        } else {
                            alert('Erro ao salvar: ' + (response.data || 'Erro desconhecido'));
                            $btn.prop('disabled', false).text(originalText);
                        }
                    },
                    error: function() {
                        alert('Erro de conexão ao tentar salvar.');
                        $btn.prop('disabled', false).text(originalText);
                    }
                });
            });
        }
        
        // Função para fechar modal
        window.closeEditModal = function() {
            $('#edit-unified-modal').remove();
        };
        
        // Fechar modal ao clicar fora
        $(document).on('click', '#edit-unified-modal', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    });
    </script>

    <style>
    .unificacao-pagination-info .vs-notice {
        padding: 8px 12px;
        margin: 0;
        background: #f0f6fc;
        border-left: 4px solid #0073aa;
    }
    
    /* Estilo para mensagem de "nenhum resultado" */
    .vs-notice {
        padding: 12px;
        margin: 15px 0;
        border-left: 4px solid;
        background: #fff;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }
    
    .vs-notice-warning {
        border-left-color: #ffb900;
        background: #fff8e5;
    }
    
    .vs-notice p {
        margin: 0.5em 0;
    }
    
    .vs-notice p:first-child {
        margin-top: 0;
    }
    
    .vs-notice p:last-child {
        margin-bottom: 0;
    }
    
    .vs-notice ul {
        margin: 0.5em 0;
    }
    
    .vs-notice a {
        color: #0073aa;
        text-decoration: none;
    }
    
    .vs-notice a:hover {
        color: #005a87;
        text-decoration: underline;
    }
    
    /* Estilos para controles de busca */
    .unificacao-search-controls {
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 15px;
        margin-bottom: 15px;
    }
    
    .unificacao-search-controls input[type="search"] {
        border: 1px solid #ddd;
        border-radius: 3px;
        padding: 6px 10px;
        font-size: 13px;
    }
    
    .unificacao-search-controls input[type="search"]:focus {
        border-color: #0073aa;
        box-shadow: 0 0 0 1px #0073aa;
        outline: none;
    }
    
    .unificacao-search-controls select {
        border: 1px solid #ddd;
        border-radius: 3px;
        padding: 6px 8px;
        font-size: 13px;
    }
    
    .unificacao-search-controls .button {
        height: auto;
        padding: 6px 12px;
        font-size: 13px;
        line-height: 1.4;
    }

    #per_page_unificacao {
        min-width: 55px;
    }
    
    /* Responsividade para controles de busca */
    @media (max-width: 768px) {
        .unificacao-search-controls > div {
            flex-direction: column;
            align-items: stretch !important;
            gap: 15px !important;
        }
        
        .unificacao-search-controls input[type="search"] {
            width: 100% !important;
        }
    }
    
    /* Estilos para paginação por botões */
    .pagination-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 15px 0;
        padding: 10px;
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .pagination-info {
        font-size: 13px;
        color: #666;
    }
    
    .pagination-controls {
        display: flex;
        gap: 5px;
        align-items: center;
    }
    
    .pagination-btn {
        min-width: 35px;
        height: 30px;
        padding: 0 8px;
        text-align: center;
        text-decoration: none;
        border: 1px solid #ccc;
        background: #f7f7f7;
        color: #555;
        border-radius: 3px;
        font-size: 12px;
        line-height: 28px;
        transition: all 0.2s ease;
    }
    
    .pagination-btn:hover {
        background: #e6e6e6;
        border-color: #999;
        color: #333;
        text-decoration: none;
    }
    
    .pagination-btn.button-primary {
        background: #0073aa;
        border-color: #0073aa;
        color: white;
        font-weight: bold;
    }
    
    .pagination-btn.button-primary:hover {
        background: #005a87;
        border-color: #005a87;
        color: white;
    }
    
    /* Responsividade para telas menores */
    @media (max-width: 768px) {
        .pagination-wrapper {
            flex-direction: column;
            gap: 10px;
        }
        
        .pagination-info {
            text-align: center;
        }
        
        .pagination-controls {
            justify-content: center;
            flex-wrap: wrap;
        }
    }

    #form-unificacao thead a {
        color: #000000;
        text-decoration: none;
        font-weight: 400;
    }

    #unificacao-table .unificacao-checkbox-column,
    #unificacao-table .col-checkbox {
        max-width: 36px;
        width: 0%;
        min-width: 1px;
    }
    </style>

    <?php
}

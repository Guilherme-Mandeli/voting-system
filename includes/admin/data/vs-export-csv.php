<?php
/**
 * Este arquivo é responsável pela exportação dos resultados de uma votação para um arquivo CSV.
 * Ele permite que os administradores exportem as respostas dos usuários para uma votação específica.
 * A exportação inclui informações detalhadas como ID do usuário, nome do usuário, perguntas, respostas, e outras informações relacionadas à votação.
 * 
 * Funções principais:
 * 
 * 1. `vs_export_csv()`: Responsável por processar a exportação dos dados da votação. 
 *    Ela verifica se o usuário tem permissão para exportar, valida a votação, recupera as respostas dos usuários e gera o arquivo CSV.
 * 2. `get_respostas_votacao()`: Recupera as respostas dos usuários para uma votação específica.
 * 
 * Parâmetros de entrada:
 *  - `votacao_id`: O ID da votação que deve ser exportada. Este parâmetro é passado via URL.
 *  - `export_csv`: Um parâmetro de controle que deve ser igual a "true" para ativar a exportação.
 * 
 * Como usar:
 * 1. Para exportar os resultados de uma votação:
 *    - O administrador pode acessar a página de resultados da votação e clicar em um link para exportar os dados.
 *    - A exportação gerará um arquivo CSV que contém informações sobre os usuários, suas respostas, e detalhes da votação.
 * 2. O CSV gerado incluirá as colunas:
 *    - ID do Usuário, Nome do Usuário, Nome da Votação, Código da Votação, Ano, Data Início, Data Fim, Quantidade de Votos, Pergunta, Resposta, Data de Preenchimento.
 * 
 * Certifique-se de que o código da votação e o título da votação estejam corretamente configurados para garantir a exportação dos dados.
 */

// Registrar a ação para exportar CSV
add_action('admin_post_export_csv_votacao', 'vs_export_csv');

/**
 * Função para exportar os resultados de uma votação para um arquivo CSV.
 * Esta função é chamada quando um link de exportação é acionado, e ela gera um arquivo CSV com as respostas dos usuários para uma votação específica.
 * 
 * Parâmetros:
 *  - $_GET['votacao_id']: ID da votação que será exportada.
 *  - $_GET['export_csv']: Deve ser igual a 'true' para que a exportação aconteça.
 * 
 * Função:
 * 1. Verifica a permissão do usuário para exportar os dados (somente administradores podem exportar).
 * 2. Valida a votação e verifica se ela existe.
 * 3. Recupera as respostas de todos os usuários para a votação.
 * 4. Cria e envia o arquivo CSV para download com os resultados da votação.
 */
function vs_export_csv() {
    // Verifique se o parâmetro "export_csv" está presente e a votação é válida
    if (isset($_GET['export_csv']) && $_GET['export_csv'] == 'true' && isset($_GET['votacao_id'])) {
        
        // Verifique se o usuário tem permissão para exportar os resultados
        if (!current_user_can('manage_options')) {
            wp_die('Você não tem permissão para exportar os dados.');
        }

        // Obtenha o ID da votação
        $votacao_id = (int) $_GET['votacao_id'];

        // Verifique se a votação existe
        if (get_post_status($votacao_id) === false) {
            wp_die('ID de votação inválido.');
        }

        // Recupere as perguntas da votação
        $perguntas = get_post_meta($votacao_id, 'vs_perguntas', true);
        if (!is_array($perguntas)) {
            $perguntas = array();
        }

        // Recupere as respostas e informações relacionadas à votação
        $respostas = get_respostas_votacao($votacao_id);

        // Verifique se há respostas para exportar
        if (empty($respostas)) {
            wp_die('Não há respostas para esta votação.');
        }

        // Criação do arquivo CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="resultados_votacao_' . $votacao_id . '.csv"');
        $output = fopen('php://output', 'w');

        // Adiciona BOM para UTF-8 (para melhor compatibilidade com Excel)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Cabeçalho do CSV
        fputcsv($output, [
            'ID Usuário', 
            'Nome Usuário', 
            'Votação', 
            'Código', 
            'Ano', 
            'Data Início', 
            'Data Fim', 
            'Quantidade de Votos', 
            'Pergunta', 
            'Resposta',
            'Data Preenchimento',
        ]);

        // Preenchendo o arquivo CSV com os dados
        foreach ($respostas as $resposta) {
            $user_id = $resposta['user_id'];
            $user_name = $resposta['user_name'];
            $user_respostas = $resposta['respostas']['respostas'];
            $data_envio = $resposta['respostas']['data_envio'];

            // Percorrer cada pergunta e resposta
            foreach ($user_respostas as $index => $resposta_usuario) {
                // Usa o label da pergunta se disponível, senão usa um padrão
                $pergunta_label = isset($perguntas[$index]['label']) 
                    ? $perguntas[$index]['label'] 
                    : 'Pergunta #' . ($index + 1);
                
                // Formata a resposta (pode ser array ou string)
                $resposta_formatada = is_array($resposta_usuario) 
                    ? implode(', ', array_map('sanitize_text_field', $resposta_usuario))
                    : sanitize_text_field($resposta_usuario);
                
                // Preencher os dados no CSV
                fputcsv($output, [
                    $user_id,
                    $user_name,
                    get_the_title($votacao_id),  // Título da votação
                    'VOT-' . date('Y', strtotime($data_envio)) . '-' . $votacao_id, // Código da votação
                    date('Y', strtotime($data_envio)),  // Ano
                    '',  // Data de início (pode ser extraída de meta se necessário)
                    '',  // Data de fim (pode ser extraída de meta se necessário)
                    1,  // Quantidade de votos (contando 1 por resposta)
                    $pergunta_label,
                    $resposta_formatada,
                    $data_envio,
                ]);
            }
        }

        fclose($output);
        exit();
    }
}


/**
 * Função para buscar as respostas da votação.
 * Esta função recupera as respostas de todos os usuários que participaram da votação.
 *
 * @param int $votacao_id ID da votação
 * @return array $respostas Lista de respostas dos usuários
 */
function get_respostas_votacao($votacao_id) {
    $respostas = [];

    // Recupera as perguntas da votação (armazenadas no post_meta)
    $perguntas = get_post_meta($votacao_id, 'vs_perguntas', true);
    if (empty($perguntas)) {
        return $respostas; // Se não há perguntas, retorna um array vazio
    }

    // Busca todos os posts de resposta para esta votação
    $args = array(
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

    $posts_resposta = get_posts($args);

    // Loop para coletar as respostas dos usuários
    foreach ($posts_resposta as $post_resposta) {
        $post_id = $post_resposta->ID;
        
        // Recupera o ID do usuário
        $user_id = get_post_meta($post_id, 'vs_usuario_id', true);
        
        // Recupera as respostas detalhadas
        $respostas_detalhadas = get_post_meta($post_id, 'vs_respostas_detalhadas', true);
        
        // Recupera a data de envio
        $data_envio = get_post_meta($post_id, 'vs_data_envio', true);
        
        if (!empty($respostas_detalhadas) && $user_id) {
            $user = get_userdata($user_id);
            $user_name = $user ? $user->display_name : 'Usuário desconhecido';
            
            // Adiciona as respostas ao array no formato esperado pela função de exportação
            $respostas[] = [
                'user_id' => $user_id,
                'user_name' => $user_name,
                'respostas' => [
                    'respostas' => $respostas_detalhadas,
                    'data_envio' => $data_envio ?: current_time('mysql')
                ],
            ];
        }
    }

    return $respostas;
}

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

        // Recupere as respostas e informações relacionadas à votação
        $respostas = get_respostas_votacao($votacao_id);

        // Verifique se há respostas para exportar
        if (empty($respostas)) {
            wp_die('Não há respostas para esta votação.');
        }

        // Criação do arquivo CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="resultados_votacao.csv"');
        $output = fopen('php://output', 'w');

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
                $pergunta = 'p' . ($index + 1); // Supondo que a pergunta seja "p1", "p2", etc.
                
                // Preencher os dados no CSV
                fputcsv($output, [
                    $user_id,
                    $user_name,
                    get_the_title($votacao_id),  // Título da votação
                    'VOT-' . date('Y', strtotime($data_envio)) . '-' . $votacao_id, // Código da votação
                    date('Y', strtotime($data_envio)),  // Ano
                    '',  // Data de início (pode ser extraída de meta)
                    '',  // Data de fim (pode ser extraída de meta)
                    1,  // Quantidade de votos (contando 1 por resposta)
                    $pergunta,
                    $resposta_usuario,
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

    // Recupera todos os usuários que participaram da votação (ajustar se necessário)
    $usuarios = get_users();  // Otimizar a consulta caso seja necessário filtrar mais usuários

    // Loop para coletar as respostas dos usuários
    foreach ($usuarios as $usuario) {
        $user_id = $usuario->ID;

        // Recupera as respostas do usuário para a votação
        $user_respostas = get_user_meta($user_id, 'vs_ultima_votacao_' . $votacao_id, true);

        // Se o usuário respondeu a votação
        if (!empty($user_respostas)) {
            $user_name = $usuario->display_name;
            // Adiciona as respostas ao array
            $respostas[] = [
                'user_id' => $user_id,
                'user_name' => $user_name,
                'respostas' => $user_respostas, // Respostas em array
            ];
        }
    }

    return $respostas;
}

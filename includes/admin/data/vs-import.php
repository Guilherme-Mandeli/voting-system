<?php
/**
 * Este arquivo é responsável pela importação de dados de uma votação a partir de um arquivo CSV.
 * Ele permite que administradores importem respostas de usuários para uma votação específica através de um arquivo CSV.
 * 
 * O sistema usa a função `vs_importar_csv_votacao` para lidar com o processo de upload do arquivo, 
 * ler os dados contidos no CSV e armazená-los nos metadados de cada usuário para uma votação específica.
 * O arquivo CSV deve conter informações como ID do usuário, nome do usuário, a pergunta, a resposta e a data de preenchimento.
 *
 * Funções principais:
 * 
 * 1. `vs_importar_csv_votacao()`: Responsável por receber o arquivo CSV, validar a permissão do usuário,
 *    processar o arquivo e armazenar os dados na base de dados do WordPress (metadados de usuários).
 * 2. `get_csv_data()`: Processa o arquivo CSV e retorna os dados de forma estruturada para importação.
 * 3. `exibir_formulario_importacao_csv()`: Exibe o formulário de upload de CSV no painel administrativo, onde os administradores podem carregar um arquivo CSV.
 * 4. `baixar_modelo_csv_votacao()`: Gera e faz o download de um modelo CSV para ser preenchido corretamente pelo usuário.
 *
 * Parâmetros de entrada:
 *  - `votacao_id`: ID da votação ao qual as respostas serão associadas.
 *  - O arquivo CSV deve ser carregado via formulário e enviado com o nome 'csv_file'.
 *
 * Como usar:
 * 1. Para importar um CSV com respostas de usuários para uma votação:
 *    - O administrador deve acessar a interface de administração do WordPress onde pode escolher um arquivo CSV e enviá-lo.
 *    - O arquivo CSV deve conter as colunas: ID do Usuário, Nome do Usuário, Votação, Código, Ano, Data Início, Data Fim, Quantidade de Votos, Pergunta, Resposta, Data Preenchimento.
 *    - As respostas serão armazenadas no metadado do usuário com a chave `vs_ultima_votacao_{votacao_id}`.
 * 
 * 2. Para gerar um modelo CSV:
 *    - O administrador pode baixar um modelo CSV que contém a estrutura de colunas necessárias para preencher corretamente as respostas.
 * 
 * Certifique-se de que o formato do CSV seja adequado, especialmente no que se refere à ordem e formato das colunas.
 */

// Registrar a ação para importar CSV
add_action('admin_post_import_csv_votacao', 'vs_importar_csv_votacao');

/**
 * Função para importar as respostas de uma votação a partir de um arquivo CSV.
 * Esta função é chamada quando o arquivo CSV é carregado via um formulário de upload no painel administrativo.
 * 
 * Parâmetros:
 *  - $_FILES['csv_file']: O arquivo CSV carregado.
 *  - $_POST['votacao_id']: O ID da votação para associar as respostas.
 * 
 * Função:
 * 1. Verifica a permissão do usuário (somente administradores podem importar dados).
 * 2. Processa o arquivo CSV e valida se o arquivo contém dados válidos.
 * 3. Para cada resposta no arquivo, ela é armazenada no metadado do usuário com a chave `vs_ultima_votacao_{votacao_id}`.
 * 4. A data de preenchimento também é salva como metadado para cada usuário.
 */
function vs_importar_csv_votacao() {
    // Verifique se o usuário tem permissão para importar os dados
    if ( !current_user_can('manage_options') ) {
        wp_die('Você não tem permissão para importar os dados.');
    }

    // Verifique se o arquivo CSV foi enviado
    if ( isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK ) {
        // Obtenha o arquivo CSV
        $csv_file = $_FILES['csv_file']['tmp_name'];
        
        // Obtenha os dados do CSV
        $dados_csv = get_csv_data($csv_file);

        // Se os dados CSV estiverem vazios, encerra a importação
        if ( empty($dados_csv) ) {
            wp_die('O arquivo CSV está vazio ou não contém dados válidos.');
        }

        // Importar as respostas para a votação
        $votacao_id = (int) $_POST['votacao_id']; // ID da votação a qual as respostas serão atribuídas
        foreach ($dados_csv as $dado) {
            $user_id = (int) $dado['user_id']; // ID do usuário que respondeu
            $respostas = $dado['respostas']; // Respostas do usuário
            $data_preenchimento = $dado['data_preenchimento']; // Data de preenchimento

            // Salva as respostas no metadado do usuário
            update_user_meta($user_id, 'vs_ultima_votacao_' . $votacao_id, $respostas);

            // Salva a data de preenchimento no metadado do usuário
            update_user_meta($user_id, 'vs_ultima_votacao_data_' . $votacao_id, $data_preenchimento);
        }

        // Redireciona para a página de sucesso
        wp_redirect(admin_url('admin.php?page=votacoes_resultados_visualizar&votacao_id=' . $votacao_id . '&import_success=true'));
        exit();
    } else {
        wp_die('Falha no upload do arquivo.');
    }
}

/**
 * Função para processar o arquivo CSV e retornar os dados em formato utilizável.
 * O CSV deve ter as colunas: ID do Usuário, Nome do Usuário, Pergunta, Resposta, Data Preenchimento.
 *
 * @param string $csv_file Caminho temporário do arquivo CSV carregado.
 * @return array $dados_csv Dados processados do CSV.
 */
function get_csv_data($csv_file) {
    $dados_csv = [];
    if (file_exists($csv_file)) {
        if (($handle = fopen($csv_file, 'r')) !== FALSE) {
            // Ignorar cabeçalho do CSV
            fgetcsv($handle);

            // Ler as linhas do CSV e armazenar os dados
            while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
                $dados_csv[] = [
                    'user_id' => $row[0],              // ID do Usuário
                    'user_name' => $row[1],            // Nome do Usuário
                    'votacao' => $row[2],              // Nome da Votação
                    'codigo' => $row[3],               // Código da Votação
                    'ano' => $row[4],                  // Ano da Votação
                    'data_inicio' => $row[5],          // Data de Início
                    'data_fim' => $row[6],             // Data de Fim
                    'quantidade_votos' => $row[7],     // Quantidade de Votos
                    'pergunta' => $row[8],             // Pergunta
                    'resposta' => $row[9],             // Resposta
                    'data_preenchimento' => $row[10], // Data de Preenchimento
                ];
            }
            fclose($handle);
        }
    }
    return $dados_csv;
}

/**
 * Gera o formulário para importar o CSV no painel administrativo.
 * Este formulário deve ser colocado na página onde o administrador gerencia as votações.
 *
 * @param int $votacao_id ID da votação para associar as respostas.
 */
function exibir_formulario_importacao_csv($votacao_id) {
    ?>
    <h2>Importar Respostas para a Votação</h2>
    <form action="<?php echo admin_url('admin-post.php?action=import_csv_votacao'); ?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="votacao_id" value="<?php echo esc_attr($votacao_id); ?>" />
        <label for="csv_file">Escolha o arquivo CSV:</label>
        <input type="file" name="csv_file" id="csv_file" accept=".csv" required />
        <input type="submit" value="Importar CSV" class="button button-primary" />
    </form>
    <?php
}

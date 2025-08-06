<?php
/**
 * Seeder para criar votações de exemplo
 * 
 * @package VotingSystem\SampleData\Seeders
 */

defined( 'ABSPATH' ) || exit;

class VS_VotacoesSeeder {
    
    private $votacoes_config = [
        [
            'title' => 'Assembleia Geral 2024 - Aprovação de Contas',
            'status' => 'aberta',
            'ano' => 2024,
            'evento' => 'assembleia-geral-2024',
            'permitir_edicao' => true,
            'data_inicio' => 0, // hoje
            'data_fim' => 30, // +30 dias
            'question_types' => ['texto', 'select', 'radio', 'checkbox']
        ],
        [
            'title' => 'Consulta sobre Regulamento Interno',
            'status' => 'em_pausa',
            'ano' => 2023,
            'evento' => null,
            'permitir_edicao' => false,
            'data_inicio' => -60, // -60 dias
            'data_fim' => 15, // +15 dias
            'question_types' => ['radio', 'checkbox', 'texto', 'select']
        ],
        [
            'title' => 'Eleições da Diretoria 2024',
            'status' => 'encerrada',
            'ano' => 2024,
            'evento' => 'eleicoes-diretoria',
            'permitir_edicao' => true,
            'data_inicio' => -90, // -90 dias
            'data_fim' => -30, // -30 dias
            'question_types' => ['checkbox', 'texto', 'select', 'radio']
        ],
        [
            'title' => 'Aprovação do Orçamento Anual',
            'status' => 'aberta',
            'ano' => 2022,
            'evento' => 'aprovacao-orcamento',
            'permitir_edicao' => false,
            'data_inicio' => -10, // -10 dias
            'data_fim' => 45, // +45 dias
            'question_types' => ['select', 'radio', 'checkbox', 'texto']
        ],
        [
            'title' => 'Mudança de Estatuto Social',
            'status' => 'encerrada',
            'ano' => 2024,
            'evento' => null,
            'permitir_edicao' => true,
            'data_inicio' => -120, // -120 dias
            'data_fim' => -60, // -60 dias
            'question_types' => ['texto', 'checkbox', 'radio', 'select']
        ]
    ];
    
    public function run() {
        require_once VS_PLUGIN_PATH . 'sample-data/factories/VotacaoFactory.php';
        require_once VS_PLUGIN_PATH . 'sample-data/factories/QuestionFactory.php';
        
        $factory = new VS_VotacaoFactory();
        $question_factory = new VS_QuestionFactory();
        
        foreach ($this->votacoes_config as $config) {
            // Verifica se já existe uma votação com este título
            $existing = get_posts([
                'post_type' => 'votacoes',
                'title' => $config['title'],
                'post_status' => 'any',
                'numberposts' => 1
            ]);
            
            if (empty($existing)) {
                // Cria a votação
                $votacao_id = $factory->create($config);
                
                if ($votacao_id) {
                    // Cria as perguntas
                    $questions = $question_factory->createSet($config['question_types']);
                    update_post_meta($votacao_id, 'vs_questions', $questions);
                    
                    // Marca como dados de exemplo
                    update_post_meta($votacao_id, '_vs_sample_data', '1');
                }
            }
        }
    }
}
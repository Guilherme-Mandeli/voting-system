<?php
/**
 * Seeder para criar eventos de exemplo
 * 
 * @package VotingSystem\SampleData\Seeders
 */

defined( 'ABSPATH' ) || exit;

class VS_EventosSeeder {
    
    private $eventos = [
        [
            'name' => 'Assembleia Geral 2024',
            'slug' => 'assembleia-geral-2024',
            'description' => 'Assembleia Geral Ordinária do ano de 2024'
        ],
        [
            'name' => 'Eleições Diretoria',
            'slug' => 'eleicoes-diretoria',
            'description' => 'Processo eleitoral para escolha da nova diretoria'
        ],
        [
            'name' => 'Consulta Pública',
            'slug' => 'consulta-publica',
            'description' => 'Consultas públicas sobre diversos temas'
        ],
        [
            'name' => 'Aprovação Orçamento',
            'slug' => 'aprovacao-orcamento',
            'description' => 'Votações relacionadas à aprovação de orçamentos'
        ]
    ];
    
    public function run() {
        foreach ($this->eventos as $evento_data) {
            // Verifica se já existe
            $existing = term_exists($evento_data['slug'], 'eventos');
            
            if (!$existing) {
                $result = wp_insert_term(
                    $evento_data['name'],
                    'eventos',
                    [
                        'slug' => $evento_data['slug'],
                        'description' => $evento_data['description']
                    ]
                );
                
                if (!is_wp_error($result)) {
                    // Marca como dados de exemplo
                    add_term_meta($result['term_id'], '_vs_sample_data', '1');
                }
            }
        }
    }
}
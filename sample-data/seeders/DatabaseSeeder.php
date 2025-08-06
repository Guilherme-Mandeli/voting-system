<?php
/**
 * Seeder principal do sistema de votação
 * Inspirado no Laravel Database Seeder
 * 
 * @package VotingSystem\SampleData\Seeders
 */

defined( 'ABSPATH' ) || exit;

class VS_DatabaseSeeder {
    
    /**
     * Executa todos os seeders
     */
    public function run() {
        $results = [];
        
        // Executa os seeders na ordem correta
        $seeders_config = [
            'VS_UsersSeeder' => 'users',
            'VS_EventosSeeder' => 'eventos', 
            'VS_VotacoesSeeder' => 'votacoes'
        ];
        
        foreach ($seeders_config as $seeder_class => $key) {
            $results[$key] = $this->call_seeder($seeder_class);
        }
        
        return [
            'success' => true,
            'message' => 'Dados populados com sucesso!',
            'data' => [
                'usuarios_criados' => $results['users']['created_users'] ?? 0,
                'usuarios_existentes' => $results['users']['existing_users'] ?? 0,
                'eventos_criados' => 4,
                'votacoes_criadas' => 5,
                'perguntas_por_votacao' => 4,
                'detalhes' => $results
            ]
        ];
    }
    
    /**
     * Chama um seeder específico
     */
    private function call_seeder($seeder_class) {
        if (class_exists($seeder_class)) {
            $seeder = new $seeder_class();
            return $seeder->run();
        }
        return null;
    }
    
    /**
     * Chama seeders múltiplos (método legado)
     */
    private function call($seeders) {
        foreach ($seeders as $seeder_class) {
            $this->call_seeder($seeder_class);
        }
    }
    
    /**
     * Remove todos os dados de exemplo
     */
    public function rollback() {
        $results = [];
        
        // Remove votações de exemplo
        $votacoes = get_posts([
            'post_type' => 'votacoes',
            'meta_query' => [
                [
                    'key' => '_vs_sample_data',
                    'value' => '1',
                    'compare' => '='
                ]
            ],
            'posts_per_page' => -1
        ]);
        
        foreach ($votacoes as $votacao) {
            wp_delete_post($votacao->ID, true);
        }
        
        // Remove eventos de exemplo
        $eventos = get_terms([
            'taxonomy' => 'eventos',
            'meta_query' => [
                [
                    'key' => '_vs_sample_data',
                    'value' => '1',
                    'compare' => '='
                ]
            ],
            'hide_empty' => false
        ]);
        
        foreach ($eventos as $evento) {
            wp_delete_term($evento->term_id, 'eventos');
        }
        
        // Remove usuários de exemplo
        require_once VS_PLUGIN_PATH . 'sample-data/seeders/UsersSeeder.php';
        $users_seeder = new VS_UsersSeeder();
        $users_result = $users_seeder->rollback();
        
        return [
            'success' => true,
            'message' => 'Dados de exemplo removidos com sucesso!',
            'data' => [
                'votacoes_removidas' => count($votacoes),
                'eventos_removidos' => count($eventos),
                'usuarios_removidos' => $users_result['removed_users'],
                'usuarios_preservados' => $users_result['preserved_users'],
                'detalhes_usuarios' => $users_result
            ]
        ];
    }
    
    /**
     * Obtém estatísticas completas
     */
    public static function get_complete_stats() {
        // Estatísticas de votações
        $votacoes_exemplo = get_posts([
            'post_type' => 'votacoes',
            'meta_query' => [
                [
                    'key' => '_vs_sample_data',
                    'value' => '1',
                    'compare' => '='
                ]
            ],
            'posts_per_page' => -1
        ]);
        
        // Estatísticas de eventos
        $eventos_exemplo = get_terms([
            'taxonomy' => 'eventos',
            'meta_query' => [
                [
                    'key' => '_vs_sample_data',
                    'value' => '1',
                    'compare' => '='
                ]
            ],
            'hide_empty' => false
        ]);
        
        // Estatísticas de usuários
        require_once VS_PLUGIN_PATH . 'sample-data/seeders/UsersSeeder.php';
        $users_stats = VS_UsersSeeder::get_stats();
        
        // Total de votações
        $total_votacoes = wp_count_posts('votacoes');
        
        return [
            'votacoes_exemplo' => count($votacoes_exemplo),
            'eventos_exemplo' => count($eventos_exemplo),
            'usuarios_exemplo' => $users_stats['total_sample_users'],
            'total_votacoes' => $total_votacoes->publish + $total_votacoes->draft,
            'has_sample_data' => count($votacoes_exemplo) > 0 || count($eventos_exemplo) > 0 || $users_stats['total_sample_users'] > 0,
            'users_details' => $users_stats['users_list']
        ];
    }
}
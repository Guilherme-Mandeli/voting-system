<?php
/**
 * Seeder para criar respostas automáticas
 * 
 * @package VotingSystem\SampleData\Seeders
 */

defined( 'ABSPATH' ) || exit;

class VS_ResponsesSeeder {
    
    public function run() {
        require_once VS_PLUGIN_PATH . 'sample-data/factories/ResponseFactory.php';
        
        $factory = new VS_ResponseFactory();
        $created_responses = 0;
        $skipped_responses = 0;
        $stats_by_votacao = [];
        
        // Busca todas as votações de exemplo
        $votacoes = get_posts([
            'post_type' => 'votacoes',
            'meta_query' => [
                [
                    'key' => '_vs_sample_data',
                    'value' => '1',
                    'compare' => '='
                ]
            ],
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        // Busca todos os usuários de exemplo
        $users = get_users([
            'meta_query' => [
                [
                    'key' => '_vs_sample_data',
                    'value' => '1',
                    'compare' => '='
                ]
            ]
        ]);
        
        if (empty($votacoes) || empty($users)) {
            return [
                'success' => false,
                'message' => 'Não foram encontradas votações ou usuários de exemplo.',
                'created_responses' => 0,
                'skipped_responses' => 0,
                'stats_by_votacao' => []
            ];
        }
        
        foreach ($votacoes as $votacao) {
            $votacao_id = $votacao->ID;
            $votacao_title = $votacao->post_title;
            $questions = get_post_meta($votacao_id, 'vs_questions', true);
            
            if (empty($questions) || !is_array($questions)) {
                continue;
            }
            
            $stats_by_votacao[$votacao_id] = [
                'title' => $votacao_title,
                'responses_created' => 0,
                'users_participated' => []
            ];
            
            foreach ($users as $user) {
                $user_id = $user->ID;
                
                // Verifica se já existe resposta deste usuário para esta votação
                $existing_response = get_posts([
                    'post_type' => 'votacao_resposta',
                    'meta_query' => [
                        [
                            'key' => 'vs_votacao_id',
                            'value' => $votacao_id,
                            'compare' => '='
                        ],
                        [
                            'key' => 'vs_usuario_id',
                            'value' => $user_id,
                            'compare' => '='
                        ]
                    ],
                    'posts_per_page' => 1
                ]);
                
                if (!empty($existing_response)) {
                    $skipped_responses++;
                    continue;
                }
                
                // Determina se o usuário deve participar desta votação
                if (!$factory->shouldUserParticipate($user_id, $votacao_id)) {
                    continue;
                }
                
                // Cria o conjunto de respostas
                $response_data = $factory->createResponseSet($questions, $user_id, $votacao_id);
                
                // Cria o post de resposta
                $response_post_id = wp_insert_post([
                    'post_title' => sprintf('Resposta de %s para %s', $user->display_name, $votacao_title),
                    'post_type' => 'votacao_resposta',
                    'post_status' => 'publish',
                    'post_author' => $user_id
                ]);
                
                if (!is_wp_error($response_post_id)) {
                    // Adiciona os metadados
                    update_post_meta($response_post_id, 'vs_votacao_id', $votacao_id);
                    update_post_meta($response_post_id, 'vs_usuario_id', $user_id);
                    update_post_meta($response_post_id, 'vs_respostas_detalhadas', $response_data['respostas_detalhadas']);
                    update_post_meta($response_post_id, 'vs_resposta_unificada', $response_data['resposta_unificada']);
                    update_post_meta($response_post_id, 'vs_data_envio', $factory->generateResponseDate($votacao_id));
                    update_post_meta($response_post_id, '_vs_sample_data', '1');
                    
                    $created_responses++;
                    $stats_by_votacao[$votacao_id]['responses_created']++;
                    $stats_by_votacao[$votacao_id]['users_participated'][] = $user->display_name;
                }
            }
        }
        
        return [
            'success' => true,
            'message' => sprintf('Criadas %d respostas automáticas com sucesso!', $created_responses),
            'created_responses' => $created_responses,
            'skipped_responses' => $skipped_responses,
            'stats_by_votacao' => $stats_by_votacao,
            'total_votacoes' => count($votacoes),
            'total_users' => count($users)
        ];
    }
    
    /**
     * Remove todas as respostas de exemplo
     */
    public function rollback() {
        $responses = get_posts([
            'post_type' => 'votacao_resposta',
            'meta_query' => [
                [
                    'key' => '_vs_sample_data',
                    'value' => '1',
                    'compare' => '='
                ]
            ],
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);
        
        $removed_count = 0;
        
        foreach ($responses as $response) {
            if (wp_delete_post($response->ID, true)) {
                $removed_count++;
            }
        }
        
        return [
            'success' => true,
            'message' => sprintf('Removidas %d respostas automáticas com sucesso!', $removed_count),
            'removed_responses' => $removed_count
        ];
    }
    
    /**
     * Obtém estatísticas das respostas de exemplo
     */
    public static function get_stats() {
        $responses = get_posts([
            'post_type' => 'votacao_resposta',
            'meta_query' => [
                [
                    'key' => '_vs_sample_data',
                    'value' => '1',
                    'compare' => '='
                ]
            ],
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        return [
            'total_sample_responses' => count($responses)
        ];
    }
}
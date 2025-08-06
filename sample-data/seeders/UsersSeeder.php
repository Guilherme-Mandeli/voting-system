<?php
/**
 * Seeder para criar usuários de exemplo
 * 
 * @package VotingSystem\SampleData\Seeders
 */

defined( 'ABSPATH' ) || exit;

class VS_UsersSeeder {
    
    private $users_config = [
        [
            'email' => 'um@teste.com.br',
            'username' => 'um@teste.com.br',
            'password' => 'um@teste.com.br',
            'display_name' => 'Usuário Um',
            'first_name' => 'Usuário',
            'last_name' => 'Um'
        ],
        [
            'email' => 'dois@teste.com.br',
            'username' => 'dois@teste.com.br',
            'password' => 'dois@teste.com.br',
            'display_name' => 'Usuário Dois',
            'first_name' => 'Usuário',
            'last_name' => 'Dois'
        ],
        [
            'email' => 'tres@teste.com.br',
            'username' => 'tres@teste.com.br',
            'password' => 'tres@teste.com.br',
            'display_name' => 'Usuário Três',
            'first_name' => 'Usuário',
            'last_name' => 'Três'
        ],
        [
            'email' => 'quatro@teste.com.br',
            'username' => 'quatro@teste.com.br',
            'password' => 'quatro@teste.com.br',
            'display_name' => 'Usuário Quatro',
            'first_name' => 'Usuário',
            'last_name' => 'Quatro'
        ],
        [
            'email' => 'cinco@teste.com.br',
            'username' => 'cinco@teste.com.br',
            'password' => 'cinco@teste.com.br',
            'display_name' => 'Usuário Cinco',
            'first_name' => 'Usuário',
            'last_name' => 'Cinco'
        ]
    ];
    
    public function run() {
        $created_users = 0;
        $existing_users = 0;
        
        foreach ($this->users_config as $user_data) {
            // Verifica se já existe um usuário com este email
            $existing_user = get_user_by('email', $user_data['email']);
            
            if ($existing_user) {
                // Se já existe, apenas marca como dados de exemplo
                update_user_meta($existing_user->ID, '_vs_sample_data', '1');
                $existing_users++;
                continue;
            }
            
            // Verifica se já existe um usuário com este username
            $existing_username = get_user_by('login', $user_data['username']);
            
            if ($existing_username) {
                // Se já existe, apenas marca como dados de exemplo
                update_user_meta($existing_username->ID, '_vs_sample_data', '1');
                $existing_users++;
                continue;
            }
            
            // Cria o novo usuário
            $user_id = wp_create_user(
                $user_data['username'],
                $user_data['password'],
                $user_data['email']
            );
            
            if (!is_wp_error($user_id)) {
                // Atualiza informações adicionais do usuário
                wp_update_user([
                    'ID' => $user_id,
                    'display_name' => $user_data['display_name'],
                    'first_name' => $user_data['first_name'],
                    'last_name' => $user_data['last_name'],
                    'role' => 'subscriber' // Define como subscriber por padrão
                ]);
                
                // Marca como dados de exemplo
                update_user_meta($user_id, '_vs_sample_data', '1');
                
                // Adiciona meta adicional para identificação
                update_user_meta($user_id, '_vs_sample_user_type', 'test_user');
                update_user_meta($user_id, '_vs_created_date', current_time('mysql'));
                
                $created_users++;
            }
        }
        
        return [
            'created_users' => $created_users,
            'existing_users' => $existing_users,
            'total_processed' => count($this->users_config)
        ];
    }
    
    /**
     * Remove usuários de exemplo
     */
    public function rollback() {
        $sample_users = get_users([
            'meta_query' => [
                [
                    'key' => '_vs_sample_data',
                    'value' => '1',
                    'compare' => '='
                ]
            ]
        ]);
        
        $removed_users = 0;
        
        foreach ($sample_users as $user) {
            // Verifica se o usuário não tem posts ou outros conteúdos importantes
            $user_posts = count_user_posts($user->ID);
            
            if ($user_posts == 0) {
                // Remove o usuário se não tem posts
                wp_delete_user($user->ID);
                $removed_users++;
            } else {
                // Se tem posts, apenas remove a marcação de exemplo
                delete_user_meta($user->ID, '_vs_sample_data');
                delete_user_meta($user->ID, '_vs_sample_user_type');
                delete_user_meta($user->ID, '_vs_created_date');
            }
        }
        
        return [
            'removed_users' => $removed_users,
            'preserved_users' => count($sample_users) - $removed_users
        ];
    }
    
    /**
     * Obtém estatísticas dos usuários de exemplo
     */
    public static function get_stats() {
        $sample_users = get_users([
            'meta_query' => [
                [
                    'key' => '_vs_sample_data',
                    'value' => '1',
                    'compare' => '='
                ]
            ]
        ]);
        
        return [
            'total_sample_users' => count($sample_users),
            'users_list' => array_map(function($user) {
                return [
                    'id' => $user->ID,
                    'email' => $user->user_email,
                    'display_name' => $user->display_name,
                    'created' => get_user_meta($user->ID, '_vs_created_date', true)
                ];
            }, $sample_users)
        ];
    }
}
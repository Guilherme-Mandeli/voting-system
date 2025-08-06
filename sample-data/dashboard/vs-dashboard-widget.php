<?php
/**
 * Widget do Dashboard para população de dados
 * 
 * @package VotingSystem\SampleData\Dashboard
 */

defined( 'ABSPATH' ) || exit;

class VS_Dashboard_Population_Widget {
    
    public function __construct() {
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widget']);
        add_action('wp_ajax_vs_populate_data', [$this, 'handle_populate_data']);
        add_action('wp_ajax_vs_clear_sample_data', [$this, 'handle_clear_data']);
        add_action('wp_ajax_vs_create_responses', [$this, 'handle_create_responses']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Carrega a interface visual para dados de exemplo
        $this->load_sample_ui();
    }
    
    /**
     * Carrega a interface visual para identificação de dados de exemplo
     */
    private function load_sample_ui() {
        require_once VS_PLUGIN_PATH . 'sample-data/vs-sample-data-ui.php';
    }
    
    public function add_dashboard_widget() {
        if (current_user_can('manage_options')) {
            wp_add_dashboard_widget(
                'vs_population_widget',
                'Sistema de Votação - Dados de Exemplo',
                [$this, 'render_widget']
            );
        }
    }
    
    public function render_widget() {
        include VS_PLUGIN_PATH . 'sample-data/dashboard/templates/widget-content.php';
    }
    
    public function enqueue_assets($hook) {
        if ($hook === 'index.php') { // Dashboard
            wp_enqueue_style(
                'vs-dashboard-widget',
                VS_PLUGIN_URL . 'sample-data/dashboard/assets/dashboard-widget.css',
                [],
                VS_PLUGIN_VERSION
            );
            
            wp_enqueue_script(
                'vs-dashboard-widget',
                VS_PLUGIN_URL . 'sample-data/dashboard/assets/dashboard-widget.js',
                ['jquery'],
                VS_PLUGIN_VERSION,
                true
            );
            
            wp_localize_script('vs-dashboard-widget', 'vsPopulateData', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vs_populate_nonce'),
                'strings' => [
                    'confirm_populate' => "Tem certeza que deseja popular os dados de exemplo?\n\nEsta ação criará:\n• 5 usuários de teste\n• 4 eventos\n• 5 votações com 4 perguntas cada",
                    'confirm_clear' => "Tem certeza que deseja remover todos os dados de exemplo?\n\nEsta ação removerá:\n• Usuários de teste (sem posts)\n• Eventos de exemplo\n• Votações de exemplo\n\nEsta ação não pode ser desfeita.",
                    'confirm_create_responses' => "Tem certeza que deseja criar respostas automáticas?\n\nEsta ação criará:\n• Respostas para todas as votações de exemplo\n• Participação realista (70-85% dos usuários)\n• 25% das respostas serão unificadas\n• Datas distribuídas no período das votações",
                    'processing' => 'Processando...',
                    'success' => 'Operação realizada com sucesso!',
                    'error' => 'Erro ao processar a solicitação.'
                ]
            ]);
        }
    }
    
    public function handle_populate_data() {
        check_ajax_referer('vs_populate_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Permissão negada');
        }
        
        try {
            // Carrega os seeders
            require_once VS_PLUGIN_PATH . 'sample-data/seeders/DatabaseSeeder.php';
            require_once VS_PLUGIN_PATH . 'sample-data/seeders/UsersSeeder.php';
            require_once VS_PLUGIN_PATH . 'sample-data/seeders/UsersSeeder.php';
            require_once VS_PLUGIN_PATH . 'sample-data/seeders/EventosSeeder.php';
            require_once VS_PLUGIN_PATH . 'sample-data/seeders/VotacoesSeeder.php';
            
            $seeder = new VS_DatabaseSeeder();
            $result = $seeder->run();
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => 'Erro ao popular dados: ' . $e->getMessage()
            ]);
        }
    }
    
    public function handle_clear_data() {
        check_ajax_referer('vs_populate_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Permissão negada');
        }
        
        try {
            require_once VS_PLUGIN_PATH . 'sample-data/seeders/DatabaseSeeder.php';
            
            $seeder = new VS_DatabaseSeeder();
            $result = $seeder->rollback();
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => 'Erro ao limpar dados: ' . $e->getMessage()
            ]);
        }
    }
    
    public function handle_create_responses() {
        check_ajax_referer('vs_populate_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Permissão negada');
        }
        
        try {
            require_once VS_PLUGIN_PATH . 'sample-data/seeders/ResponsesSeeder.php';
            
            $seeder = new VS_ResponsesSeeder();
            $result = $seeder->run();
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => 'Erro ao criar respostas: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtém estatísticas dos dados atuais (método atualizado)
     */
    public static function get_stats() {
        require_once VS_PLUGIN_PATH . 'sample-data/seeders/DatabaseSeeder.php';
        return VS_DatabaseSeeder::get_complete_stats();
    }
}

// Inicializa o widget
new VS_Dashboard_Population_Widget();
<?php
defined( 'ABSPATH' ) || exit;

/**
 * Bootstrap do Voting System
 */
class VS_Bootstrap {
    
    public function __construct() {
        add_action( 'init', array( $this, 'init' ), 5 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }
    
    public function init() {
        $this->load_core();
        $this->load_helpers();
        $this->load_frontend();
        $this->load_admin();
        $this->load_ajax();
    }
    
    private function load_core() {
        // CPTs - Carrega e registra imediatamente
        require_once VS_PLUGIN_PATH . 'includes/core/cpt/vs-register-cpt-voting.php';
        vs_register_cpt_voting(); // Chama a função diretamente
        
        require_once VS_PLUGIN_PATH . 'includes/core/cpt/vs-register-cpt-answer.php';
        if ( function_exists( 'vs_register_cpt_answer' ) ) {
            vs_register_cpt_answer(); // Chama se existir
        }
        
        // Taxonomias
        require_once VS_PLUGIN_PATH . 'includes/core/tax/vs-register-tax-event.php';
        
        // Submission
        require_once VS_PLUGIN_PATH . 'includes/core/submission/vs-handle-form-submit.php';
        
        // Cron (quando implementado)
        // require_once VS_PLUGIN_PATH . 'includes/core/cron/vs-voting-scheduler.php';
    }
    
    private function load_helpers() {
        require_once VS_PLUGIN_PATH . 'helpers/vs-utils-data.php';
        require_once VS_PLUGIN_PATH . 'helpers/vs-utils-votacoes.php';
        require_once VS_PLUGIN_PATH . 'helpers/vs-utils-templates.php';
        require_once VS_PLUGIN_PATH . 'helpers/vs-utils-permissions.php';
    }
    
    private function load_frontend() {
        require_once VS_PLUGIN_PATH . 'includes/frontend/shortcodes/vs-shortcode-voting-form.php';
        require_once VS_PLUGIN_PATH . 'includes/frontend/shortcodes/vs-shortcode-thank-you.php';
        require_once VS_PLUGIN_PATH . 'includes/frontend/shortcodes/vs-shortcode-votacoes-feed.php';
        require_once VS_PLUGIN_PATH . 'includes/frontend/shortcodes/vs-shortcode-votacoes-home-feed.php';
    }
    
    private function load_admin() {
        if ( is_admin() ) {
            require_once VS_PLUGIN_PATH . 'includes/admin/menus/vs-admin-menus.php';
            require_once VS_PLUGIN_PATH . 'includes/admin/pages/vs-page-results-list.php';
            require_once VS_PLUGIN_PATH . 'includes/admin/pages/vs-page-results-details.php';
            require_once VS_PLUGIN_PATH . 'includes/admin/pages/vs-page-results-unificacao.php';
            require_once VS_PLUGIN_PATH . 'includes/admin/data/vs-export-csv.php';
            require_once VS_PLUGIN_PATH . 'includes/admin/data/vs-import.php';
            
            // Metaboxes
            require_once VS_PLUGIN_PATH . 'metaboxes/vs-metabox-voting-info.php';
            require_once VS_PLUGIN_PATH . 'metaboxes/vs-metabox-questions.php';
            require_once VS_PLUGIN_PATH . 'metaboxes/vs-metabox-answer-details.php';
        }
    }
    
    private function load_ajax() {
        require_once VS_PLUGIN_PATH . 'includes/ajax/get-user-votes.php';
        require_once VS_PLUGIN_PATH . 'includes/ajax/vs-handle-get-unificacao-group.php';
        require_once VS_PLUGIN_PATH . 'includes/ajax/vs-handle-update-unificacao.php';
    }
    
    public function enqueue_public_assets() {
        // CSS principal
        wp_enqueue_style( 'vs-public-style', VS_PLUGIN_URL . 'assets/css/public.css', array(), VS_PLUGIN_VERSION );
        
        // CSS específicos para feeds
        wp_enqueue_style( 'vs-votacoes-feed-style', VS_PLUGIN_URL . 'assets/css/vs-votacoes-feed.css', array(), VS_PLUGIN_VERSION );
        wp_enqueue_style( 'vs-votacoes-home-style', VS_PLUGIN_URL . 'assets/css/vs-votacoes-home.css', array(), VS_PLUGIN_VERSION );
        
        // JavaScript principal
        wp_enqueue_script( 'vs-public-script', VS_PLUGIN_URL . 'assets/js/public.js', array( 'jquery' ), VS_PLUGIN_VERSION, true );
    }
    
    public function enqueue_admin_assets() {
        // CSS administrativo
        wp_enqueue_style( 'vs-admin-style', VS_PLUGIN_URL . 'assets/css/admin.css', array(), VS_PLUGIN_VERSION );
        
        // JavaScript administrativo
        wp_enqueue_script( 'vs-admin-script', VS_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), VS_PLUGIN_VERSION, true );
        
        // JavaScript AJAX específicos para páginas administrativas
        $current_screen = get_current_screen();
        
        // Script para página de resultados (modal de respostas)
        if ( $current_screen && strpos( $current_screen->id, 'votacoes_page_vs-results' ) !== false ) {
            wp_enqueue_script( 
                'vs-votacao-ajax', 
                VS_PLUGIN_URL . 'assets/js/ajax/votacao-ajax.js', 
                array( 'jquery' ), 
                VS_PLUGIN_VERSION, 
                true 
            );
            
            // Localizar script para AJAX
            wp_localize_script( 'vs-votacao-ajax', 'vsAjax', array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'vs_ajax_nonce' )
            ));
        }
        
        // Script para página de unificação
        if ( $current_screen && strpos( $current_screen->id, 'votacoes_page_vs-results-unificacao' ) !== false ) {
            wp_enqueue_script( 
                'vs-unificacao-modal', 
                VS_PLUGIN_URL . 'assets/js/ajax/vs-handle-get-unificacao-group.js', 
                array( 'jquery' ), 
                VS_PLUGIN_VERSION, 
                true 
            );
            
            // Localizar script para modal de unificação
            wp_localize_script( 'vs-unificacao-modal', 'vsUnificacaoModalData', array(
                'ajaxurl'    => admin_url( 'admin-ajax.php' ),
                'nonce'      => wp_create_nonce( 'vs_unificacao_nonce' ),
                'votacao_id' => isset( $_GET['votacao_id'] ) ? intval( $_GET['votacao_id'] ) : 0
            ));
        }
    }
}

new VS_Bootstrap();

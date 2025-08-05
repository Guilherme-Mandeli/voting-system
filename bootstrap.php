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
        require_once VS_PLUGIN_PATH . 'includes/core/submission/vs-process-voting.php';
        
        // Cron (quando implementado)
        // require_once VS_PLUGIN_PATH . 'includes/core/cron/vs-voting-scheduler.php';
    }
    
    private function load_helpers() {
        require_once VS_PLUGIN_PATH . 'helpers/vs-utils-data.php';
        require_once VS_PLUGIN_PATH . 'helpers/vs-utils-votacoes.php';
        require_once VS_PLUGIN_PATH . 'helpers/vs-utils-templates.php';
        require_once VS_PLUGIN_PATH . 'helpers/vs-utils-permissions.php';
        require_once VS_PLUGIN_PATH . 'helpers/vs-utils-css-loader.php';
        require_once VS_PLUGIN_PATH . 'helpers/vs-utils-admin-ranking.php';
        
        // Carrega partials dos templates admin
        $this->load_admin_template_partials();
    }
    
    /**
     * Carrega os partials dos templates administrativos
     */
    private function load_admin_template_partials() {
        $partials_path = VS_PLUGIN_PATH . 'templates/admin/partials/';
        
        if ( is_admin() ) {
            // Carrega partials de ranking
            if ( file_exists( $partials_path . 'ranking-filters.php' ) ) {
                require_once $partials_path . 'ranking-filters.php';
            }
            if ( file_exists( $partials_path . 'ranking-table.php' ) ) {
                require_once $partials_path . 'ranking-table.php';
            }
            if ( file_exists( $partials_path . 'ranking-modal.php' ) ) {
                require_once $partials_path . 'ranking-modal.php';
            }
        }
    }
    
    private function load_frontend() {
        // A classe CSS já é carregada via helpers
        require_once VS_PLUGIN_PATH . 'includes/frontend/shortcodes/vs-shortcode-voting-form.php';
        require_once VS_PLUGIN_PATH . 'includes/frontend/shortcodes/vs-shortcode-thank-you.php';
        require_once VS_PLUGIN_PATH . 'includes/frontend/shortcodes/vs-shortcode-votacoes-feed.php';
        require_once VS_PLUGIN_PATH . 'includes/frontend/shortcodes/vs-shortcode-votacoes-home-feed.php';
        require_once VS_PLUGIN_PATH . 'includes/frontend/shortcodes/vs-shortcode-user-votacoes.php';
        require_once VS_PLUGIN_PATH . 'includes/frontend/shortcodes/vs-shortcode-votacoes-generic.php';
    }
    
    public function enqueue_public_assets() {
        // Carrega apenas o CSS base
        wp_enqueue_style( 'vs-public-style', VS_PLUGIN_URL . 'assets/css/public.css', array(), VS_PLUGIN_VERSION );
        
        // Carrega CSS condicionalmente baseado nos shortcodes presentes
        VS_CSS_Conditional_Loader::enqueue_conditional_css();
        
        // JavaScript principal
        wp_enqueue_script( 'vs-public-script', VS_PLUGIN_URL . 'assets/js/public.js', array( 'jquery' ), VS_PLUGIN_VERSION, true );
        
    }
    
    private function load_admin() {
        if ( is_admin() ) {
            require_once VS_PLUGIN_PATH . 'includes/admin/menus/vs-admin-menus.php';
            require_once VS_PLUGIN_PATH . 'includes/admin/pages/vs-page-results-list.php';
            require_once VS_PLUGIN_PATH . 'includes/admin/pages/vs-page-results-details.php';
            require_once VS_PLUGIN_PATH . 'includes/admin/pages/vs-page-results-unificacao.php';
            require_once VS_PLUGIN_PATH . 'includes/admin/data/vs-export-csv.php';
            require_once VS_PLUGIN_PATH . 'includes/admin/data/vs-import.php';
            require_once VS_PLUGIN_PATH . 'includes/admin/data/vs-response-actions.php';
            
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
        require_once VS_PLUGIN_PATH . 'includes/ajax/vs-handle-votacao-anterior.php';
    }
    
    public function enqueue_admin_assets() {
        // CSS administrativo - SEMPRE carregado nas páginas admin
        wp_enqueue_style( 'vs-admin-style', VS_PLUGIN_URL . 'assets/css/admin.css', array(), VS_PLUGIN_VERSION );
        
        // JavaScript administrativo básico
        wp_enqueue_script( 'vs-admin-utils', VS_PLUGIN_URL . 'assets/js/admin/utils.js', array( 'jquery' ), VS_PLUGIN_VERSION, true );
        wp_enqueue_script( 'vs-admin-imported-answers', VS_PLUGIN_URL . 'assets/js/admin/imported-answers.js', array( 'jquery', 'vs-admin-utils' ), VS_PLUGIN_VERSION, true );
        wp_enqueue_script( 'vs-admin-field-type-handler', VS_PLUGIN_URL . 'assets/js/admin/field-type-handler.js', array( 'jquery', 'vs-admin-imported-answers' ), VS_PLUGIN_VERSION, true );
        wp_enqueue_script( 'vs-admin-voting-modal', VS_PLUGIN_URL . 'assets/js/admin/voting-modal.js', array( 'jquery', 'vs-admin-utils' ), VS_PLUGIN_VERSION, true );
        wp_enqueue_script( 'vs-admin-main', VS_PLUGIN_URL . 'assets/js/admin/main.js', array( 'jquery', 'vs-admin-utils', 'vs-admin-field-type-handler', 'vs-admin-imported-answers', 'vs-admin-voting-modal' ), VS_PLUGIN_VERSION, true );
        
        // Localizar script admin básico
        wp_localize_script( 'vs-admin-main', 'vs_admin', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( VS_Nonce_Actions::AJAX_ADMIN )
        ));
        
        // Obtém informações da tela atual
        $current_screen = get_current_screen();
        $page = isset( $_GET['page'] ) ? $_GET['page'] : '';
        
        // Script para páginas de resultados (modal "Ver Respostas")
        if ( $page === 'votacoes_resultados_visualizar' ||
             ( $current_screen && strpos( $current_screen->id, 'votacoes_page_votacoes_resultados' ) !== false ) ) {
            
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
        
        // Script para ranking (páginas de resultados com ranking)
        if ( $page === 'votacoes_resultados_visualizar' ||
             ( $current_screen && strpos( $current_screen->id, 'votacoes_page_votacoes_resultados' ) !== false ) ) {
            
            wp_enqueue_script( 
                'vs-admin-ranking', 
                VS_PLUGIN_URL . 'assets/js/admin-ranking.js', 
                array( 'jquery' ), 
                VS_PLUGIN_VERSION, 
                true 
            );
            
            // Localizar script para ranking
            wp_localize_script( 'vs-admin-ranking', 'vsAdminAjax', array(
                'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                'adminUrl'  => admin_url(),
                'nonce'     => wp_create_nonce( 'vs_ranking_nonce' )
            ));
        }
        
        // Script para página de unificação
        if ( ( $page === 'votacoes_resultados_visualizar' && isset( $_GET['subpage'] ) && $_GET['subpage'] === 'unificacao' ) ||
             ( $current_screen && strpos( $current_screen->id, 'votacoes_page_vs-results-unificacao' ) !== false ) ) {
            
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

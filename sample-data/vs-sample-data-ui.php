<?php
/**
 * Interface visual para identificação de dados de exemplo
 * Gerencia tags e alertas para votações, usuários e eventos de exemplo
 * 
 * @package VotingSystem\SampleData
 */

defined( 'ABSPATH' ) || exit;

class VS_Sample_Data_UI {
    
    public function __construct() {
        // Hooks para a listagem de posts - APENAS CSS
        add_action( 'admin_head', [$this, 'add_sample_tag_styles'] );
        
        // Hooks para usuários de exemplo
        add_action( 'admin_head', [$this, 'add_sample_users_styles'] );
        
        // Hooks para taxonomia eventos de exemplo
        add_action( 'admin_head', [$this, 'add_sample_events_styles'] );
        
        // Hooks para o post individual
        add_action( 'edit_form_after_title', [$this, 'add_sample_data_alert'] );
        add_action( 'admin_head', [$this, 'add_sample_alert_styles'] );
    }
    
    /* ------------------------------------------------------------------------- *
     * Tag "Exemplo" na listagem de posts
     * ------------------------------------------------------------------------- */
    
    /**
     * Adiciona CSS para a tag de exemplo na listagem usando ::before
     */
    public function add_sample_tag_styles() {
        $screen = get_current_screen();
        
        if ( $screen && $screen->post_type === 'votacoes' && $screen->base === 'edit' ) {
            // Obter IDs dos posts de exemplo para aplicar CSS específico
            $sample_posts = get_posts([
                'post_type' => 'votacoes',
                'meta_key' => '_vs_sample_data',
                'meta_value' => '1',
                'posts_per_page' => -1,
                'fields' => 'ids'
            ]);
            
            if (!empty($sample_posts)) {
                $selectors = array_map(function($id) {
                    return "#post-{$id} .row-title::before";
                }, $sample_posts);
                
                $css_selector = implode(', ', $selectors);
                ?>
            <style>
                
            <?php echo $css_selector; ?> {
                content: "Exemplo";
                background:rgba(255, 107, 53, 0.5);
                color: #454545;
                padding: 2px 5px 2px 6px;
                border-radius: 30px;
                font-size: 11px;
                font-weight: normal;
                margin-right: 8px;
                display: inline-block;
                line-height: initial;
                box-shadow: 0 1px 2px rgba(0,0,0,0.2);
            }
            </style>

            <?php
            }
        }
    }
    
    /* ------------------------------------------------------------------------- *
     * Tag "Exemplo" na listagem de usuários
     * ------------------------------------------------------------------------- */
    
    /**
     * Adiciona CSS para a tag de exemplo na listagem de usuários usando ::before
     */
    public function add_sample_users_styles() {
        $screen = get_current_screen();
        
        if ( $screen && $screen->base === 'users' ) {
            // Obter IDs dos usuários de exemplo para aplicar CSS específico
            $sample_users = get_users([
                'meta_key' => '_vs_sample_data',
                'meta_value' => '1',
                'fields' => 'ID'
            ]);
            
            if (!empty($sample_users)) {
                $selectors = array_map(function($id) {
                    return "#user-{$id} .username::before";
                }, $sample_users);
                
                $css_selector = implode(', ', $selectors);
                ?>
            <style>
                
            <?php echo $css_selector; ?> {
                content: "Exemplo";
                background:rgba(255, 107, 53, 0.5);
                color: #454545;
                padding: 2px 5px 2px 6px;
                border-radius: 30px;
                font-size: 11px;
                font-weight: normal;
                margin-right: 8px;
                display: inline-block;
                line-height: initial;
                box-shadow: 0 1px 2px rgba(0,0,0,0.2);
            }
            </style>

            <?php
            }
        }
    }
    
    /* ------------------------------------------------------------------------- *
     * Alert no post de exemplo
     * ------------------------------------------------------------------------- */
    
    /**
     * Adiciona alert de exemplo no post
     */
    public function add_sample_data_alert() {
        global $post;
        
        // Só aplica na tela de edição de votações
        if ( ! is_admin() || ! $post || $post->post_type !== 'votacoes' ) {
            return;
        }
        
        $is_sample = get_post_meta( $post->ID, '_vs_sample_data', true );
        
        if ( $is_sample === '1' ) {
            ?>
            <div class="notice notice-warning vs-sample-alert" style="margin: 20px 0; padding: 15px; border-left: 4px solid #ff6b35;">
                <div style="display: flex; align-items: flex-start; gap: 10px;">
                    <span class="dashicons dashicons-warning" style="color: #ff6b35; margin-top: 2px;"></span>
                    <div>
                        <h4 style="margin: 0 0 8px 0; color: #ff6b35;">
                            <strong>DADOS DE EXEMPLO</strong>
                        </h4>
                        <p style="margin: 0 0 8px 0;">
                            Esta votação foi <strong>gerada automaticamente</strong> pelo sistema de dados de exemplo.
                        </p>
                        <p style="margin: 0 0 8px 0; font-size: 13px; color: #666;">
                            <strong>Atenção:</strong> Ao usar o botão "Limpar Dados de Exemplo" no painel, 
                            <strong>todas as alterações realizadas aqui serão perdidas permanentemente</strong>.
                        </p>
                        <div style="margin-top: 10px; padding: 8px; background: #fff3cd; border-radius: 4px; font-size: 12px;">
                            💡 <strong>Dica:</strong> Para manter esta votação como oficial, 
                            <a href="#" onclick="vsSampleDataUI.makeOfficial(<?php echo $post->ID; ?>); return false;" style="color: #0073aa;">
                                clique aqui para remover a marcação de exemplo
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                // Move o alert para o topo da página
                $('.vs-sample-alert').prependTo('#poststuff');
            });
            
            // Objeto para gerenciar ações da UI
            window.vsSampleDataUI = {
                makeOfficial: function(postId) {
                    if (confirm('Tem certeza que deseja remover a marcação de exemplo desta votação?\n\nEla não será mais removida ao limpar os dados de exemplo.')) {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'vs_make_voting_official',
                                post_id: postId,
                                nonce: '<?php echo wp_create_nonce('vs_make_official_nonce'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    $('.vs-sample-alert').fadeOut(300, function() {
                                        $(this).remove();
                                    });
                                    
                                    // Mostra mensagem de sucesso
                                    $('<div class="notice notice-success is-dismissible"><p><strong>Votação oficializada com sucesso!</strong> Ela não será mais removida ao limpar dados de exemplo.</p></div>')
                                        .prependTo('#poststuff')
                                        .delay(3000)
                                        .fadeOut();
                                        
                                    // Recarrega a página para atualizar a visualização
                                    setTimeout(function() {
                                        location.reload();
                                    }, 1000);
                                } else {
                                    alert('Erro ao oficializar votação: ' + (response.data.message || 'Erro desconhecido'));
                                }
                            },
                            error: function() {
                                alert('Erro de comunicação com o servidor.');
                            }
                        });
                    }
                }
            };
            </script>
            <?php
        }
    }
    
    /**
     * Adiciona CSS adicional para o alert
     */
    /* ------------------------------------------------------------------------- *
     * Tag "Exemplo" na listagem de eventos (taxonomia)
     * ------------------------------------------------------------------------- */
    
    /**
     * Adiciona CSS para a tag de exemplo na listagem de eventos usando ::before
     */
    public function add_sample_events_styles() {
        $screen = get_current_screen();
        
        if ( $screen && $screen->taxonomy === 'eventos' && $screen->base === 'edit-tags' ) {
            // Obter IDs dos eventos de exemplo para aplicar CSS específico
            $sample_events = get_terms([
                'taxonomy' => 'eventos',
                'meta_key' => '_vs_sample_data',
                'meta_value' => '1',
                'hide_empty' => false,
                'fields' => 'ids'
            ]);
            
            if (!empty($sample_events)) {
                $selectors = array_map(function($id) {
                    return "#tag-{$id} .row-title::before";
                }, $sample_events);
                
                $css_selector = implode(', ', $selectors);
                ?>
            <style>
                
            <?php echo $css_selector; ?> {
                content: "Exemplo";
                background:rgba(255, 107, 53, 0.5);
                color: #454545;
                padding: 2px 5px 2px 6px;
                border-radius: 30px;
                font-size: 11px;
                font-weight: normal;
                margin-right: 8px;
                display: inline-block;
                line-height: initial;
                box-shadow: 0 1px 2px rgba(0,0,0,0.2);
            }
            </style>

            <?php
            }
        }
    }
    
    /**
     * Adiciona CSS adicional para o alert
     */
    public function add_sample_alert_styles() {
        $screen = get_current_screen();
        
        if ( $screen && $screen->post_type === 'votacoes' && $screen->base === 'post' ) {
            ?>
            <style>
            .vs-sample-alert {
                background: #fff8e1 !important;
                border-left: 4px solid #ff6b35 !important;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                border-radius: 4px;
            }
            
            .vs-sample-alert .dashicons-warning {
                font-size: 20px;
                width: 20px;
                height: 20px;
            }
            
            .vs-sample-alert h4 {
                font-size: 14px;
                line-height: 1.4;
            }
            
            .vs-sample-alert p {
                font-size: 13px;
                line-height: 1.5;
            }
            
            .vs-sample-alert a {
                text-decoration: none;
                font-weight: 500;
            }
            
            .vs-sample-alert a:hover {
                text-decoration: underline;
            }
            </style>
            <?php
        }
    }
    
    /* ------------------------------------------------------------------------- *
     * AJAX Handler para oficializar votação
     * ------------------------------------------------------------------------- */
    
    /**
     * Handler AJAX para remover marcação de exemplo
     */
    public function handle_make_official() {
        check_ajax_referer('vs_make_official_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permissão negada']);
        }
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id || get_post_type($post_id) !== 'votacoes') {
            wp_send_json_error(['message' => 'ID de votação inválido']);
        }
        
        // Remove a marcação de exemplo
        delete_post_meta($post_id, '_vs_sample_data');
        
        wp_send_json_success([
            'message' => 'Votação oficializada com sucesso!'
        ]);
    }
}

// Inicializa a classe
$vs_sample_ui = new VS_Sample_Data_UI();

// Registra o handler AJAX
add_action('wp_ajax_vs_make_voting_official', [$vs_sample_ui, 'handle_make_official']);
<?php
defined( 'ABSPATH' ) || exit;

/**
 * Helper para carregamento condicional de CSS baseado em shortcodes
 * 
 * Este sistema detecta quais shortcodes estão presentes no conteúdo da página
 * e carrega apenas os arquivos CSS necessários para cada shortcode.
 */

class VS_CSS_Conditional_Loader {
    
    /**
     * Mapeamento de shortcodes para arquivos CSS
     */
    private static $shortcode_css_map = [
        // Shortcodes de formulário
        'votacao_formulario' => 'vs-voting-form.css',
        'votacao_obrigado' => 'vs-thank-you.css',
        
        // Shortcodes de feeds
        'votacoes_feed' => 'vs-votacoes-feed.css',
        'votacoes_home_feed' => [
            'vs-votacoes-home.css',
            'vs-votacoes-home-filtros.css'
        ],
        
        // Shortcodes de área do usuário
        'votacoes_usuario_ativas' => 'vs-user-votacoes.css',
        'votacoes_usuario_encerradas' => 'vs-user-votacoes.css',
        'votacoes_disponiveis' => 'vs-user-votacoes.css',
        
        // Shortcode genérico
        'votacoes_display' => 'vs-votacoes-generic.css'
    ];
    
    /**
     * Detecta shortcodes no conteúdo da página atual
     */
    public static function detect_shortcodes_in_content() {
        global $post;
        
        $shortcodes_found = [];
        
        if ( ! $post ) {
            return $shortcodes_found;
        }
        
        $content = $post->post_content;
        
        // Verifica cada shortcode mapeado
        foreach ( self::$shortcode_css_map as $shortcode => $css_files ) {
            if ( has_shortcode( $content, $shortcode ) ) {
                $shortcodes_found[] = $shortcode;
            }
        }
        
        // Também verifica em widgets (se necessário)
        $shortcodes_found = array_merge( $shortcodes_found, self::detect_shortcodes_in_widgets() );
        
        return array_unique( $shortcodes_found );
    }
    
    /**
     * Detecta shortcodes em widgets
     */
    private static function detect_shortcodes_in_widgets() {
        $shortcodes_found = [];
        
        // Obtém todos os widgets ativos
        $sidebars_widgets = wp_get_sidebars_widgets();
        
        if ( ! is_array( $sidebars_widgets ) ) {
            return $shortcodes_found;
        }
        
        foreach ( $sidebars_widgets as $sidebar_id => $widget_ids ) {
            if ( ! is_array( $widget_ids ) ) {
                continue;
            }
            
            foreach ( $widget_ids as $widget_id ) {
                // Verifica widgets de texto
                if ( strpos( $widget_id, 'text-' ) === 0 ) {
                    $text_widgets = get_option( 'widget_text', [] );
                    $widget_number = str_replace( 'text-', '', $widget_id );
                    
                    if ( isset( $text_widgets[$widget_number]['text'] ) ) {
                        $widget_content = $text_widgets[$widget_number]['text'];
                        
                        foreach ( self::$shortcode_css_map as $shortcode => $css_files ) {
                            if ( has_shortcode( $widget_content, $shortcode ) ) {
                                $shortcodes_found[] = $shortcode;
                            }
                        }
                    }
                }
            }
        }
        
        return $shortcodes_found;
    }
    
    /**
     * Carrega CSS baseado nos shortcodes detectados
     */
    public static function enqueue_conditional_css() {
        $shortcodes_found = self::detect_shortcodes_in_content();
        
        if ( empty( $shortcodes_found ) ) {
            return;
        }
        
        foreach ( $shortcodes_found as $shortcode ) {
            if ( isset( self::$shortcode_css_map[$shortcode] ) ) {
                $css_files = self::$shortcode_css_map[$shortcode];
                
                // Se for um array de arquivos CSS
                if ( is_array( $css_files ) ) {
                    foreach ( $css_files as $css_file ) {
                        self::enqueue_css_file( $shortcode, $css_file );
                    }
                } else {
                    // Se for um único arquivo CSS
                    self::enqueue_css_file( $shortcode, $css_files );
                }
            }
        }
    }
    
    /**
     * Enfileira um arquivo CSS específico
     */
    private static function enqueue_css_file( $shortcode, $css_file ) {
        $handle = 'vs-' . str_replace( '.css', '', $css_file );
        
        // Primeiro tenta na pasta public
        $public_file_path = VS_PLUGIN_URL . 'assets/css/public/' . $css_file;
        $public_local_path = VS_PLUGIN_PATH . 'assets/css/public/' . $css_file;
        
        if ( file_exists( $public_local_path ) ) {
            wp_enqueue_style( $handle, $public_file_path, [], VS_PLUGIN_VERSION );
            return;
        }
        
        // Fallback para a pasta raiz
        $root_file_path = VS_PLUGIN_URL . 'assets/css/' . $css_file;
        $root_local_path = VS_PLUGIN_PATH . 'assets/css/' . $css_file;
        
        if ( file_exists( $root_local_path ) ) {
            wp_enqueue_style( $handle, $root_file_path, [], VS_PLUGIN_VERSION );
        }
    }
    
    /**
     * Adiciona um novo mapeamento de shortcode para CSS
     */
    public static function add_shortcode_css_mapping( $shortcode, $css_files ) {
        self::$shortcode_css_map[$shortcode] = $css_files;
    }
    
    /**
     * Remove um mapeamento de shortcode
     */
    public static function remove_shortcode_css_mapping( $shortcode ) {
        unset( self::$shortcode_css_map[$shortcode] );
    }
    
    /**
     * Obtém todos os mapeamentos atuais
     */
    public static function get_shortcode_css_mappings() {
        return self::$shortcode_css_map;
    }
    
    /**
     * Força o carregamento de CSS para shortcodes específicos
     * Útil para páginas que usam AJAX ou carregamento dinâmico
     */
    public static function force_enqueue_shortcode_css( $shortcodes ) {
        if ( ! is_array( $shortcodes ) ) {
            $shortcodes = [ $shortcodes ];
        }
        
        foreach ( $shortcodes as $shortcode ) {
            if ( isset( self::$shortcode_css_map[$shortcode] ) ) {
                $css_files = self::$shortcode_css_map[$shortcode];
                
                if ( is_array( $css_files ) ) {
                    foreach ( $css_files as $css_file ) {
                        self::enqueue_css_file( $shortcode, $css_file );
                    }
                } else {
                    self::enqueue_css_file( $shortcode, $css_files );
                }
            }
        }
    }
    
    /**
     * Carrega CSS inline para page builders
     * Usado quando wp_enqueue_style não funciona (AJAX, page builders)
     */
    public static function output_inline_css_for_shortcode( $shortcode ) {
        static $css_cache = [];
        
        if ( isset( $css_cache[$shortcode] ) ) {
            return $css_cache[$shortcode];
        }
        
        if ( ! isset( self::$shortcode_css_map[$shortcode] ) ) {
            $css_cache[$shortcode] = '<!-- CSS file not found for shortcode: ' . $shortcode . ' -->';
            return $css_cache[$shortcode];
        }
        
        $css_files = self::$shortcode_css_map[$shortcode];
        $css_output = '';
        
        if ( ! is_array( $css_files ) ) {
            $css_files = [ $css_files ];
        }
        
        foreach ( $css_files as $css_file ) {
            // Primeiro tenta na pasta public
            $public_css_file_path = VS_PLUGIN_PATH . 'assets/css/public/' . $css_file;
            
            if ( file_exists( $public_css_file_path ) ) {
                $css_content = file_get_contents( $public_css_file_path );
                if ( $css_content ) {
                    $css_output .= $css_content . "\n";
                }
                continue;
            }
            
            // Fallback para a pasta raiz
            $root_css_file_path = VS_PLUGIN_PATH . 'assets/css/' . $css_file;
            
            if ( file_exists( $root_css_file_path ) ) {
                $css_content = file_get_contents( $root_css_file_path );
                if ( $css_content ) {
                    $css_output .= $css_content . "\n";
                }
            }
        }
        
        if ( $css_output ) {
            $css_id = 'vs-' . str_replace( '_', '-', $shortcode ) . '-inline-css';
            $css_cache[$shortcode] = '<style id="' . $css_id . '" type="text/css">' . "\n" . $css_output . "\n" . '</style>' . "\n";
        } else {
            $css_cache[$shortcode] = '<!-- CSS not found for shortcode: ' . $shortcode . ' -->' . "\n";
        }
        
        return $css_cache[$shortcode];
    }
    
    /**
     * Detecta se estamos em contexto de page builder
     */
    public static function is_page_builder_context() {
        // DIVI
        if ( defined( 'ET_BUILDER_VERSION' ) || isset( $_GET['et_fb'] ) || isset( $_POST['et_fb'] ) ) {
            return true;
        }
        
        // Elementor
        if ( defined( 'ELEMENTOR_VERSION' ) && (
            isset( $_GET['elementor-preview'] ) || 
            ( isset( $_GET['action'] ) && $_GET['action'] === 'elementor' )
        )) {
            return true;
        }
        
        // Beaver Builder
        if ( class_exists( 'FLBuilder' ) && FLBuilder::is_builder_enabled() ) {
            return true;
        }
        
        // Visual Composer
        if ( function_exists( 'vc_is_inline' ) && vc_is_inline() ) {
            return true;
        }
        
        // Gutenberg editor
        if ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Detecta se é uma requisição AJAX
     */
    public static function is_ajax_request() {
        return defined( 'DOING_AJAX' ) && DOING_AJAX;
    }
    
    /**
     * Estratégia inteligente de carregamento de CSS
     * Tenta wp_enqueue_style primeiro, depois fallback para inline
     */
    public static function ensure_css_for_shortcode( $shortcode ) {
        // Se não é page builder nem AJAX, tenta enqueue normal
        if ( ! self::is_page_builder_context() && ! self::is_ajax_request() ) {
            if ( isset( self::$shortcode_css_map[$shortcode] ) ) {
                $css_files = self::$shortcode_css_map[$shortcode];
                
                if ( is_array( $css_files ) ) {
                    foreach ( $css_files as $css_file ) {
                        self::enqueue_css_file( $shortcode, $css_file );
                    }
                } else {
                    self::enqueue_css_file( $shortcode, $css_files );
                }
                return;
            }
        }
        
        // Para page builders, AJAX ou se enqueue falhou, usa CSS inline
        add_action( 'wp_footer', function() use ( $shortcode ) {
            $handle = 'vs-' . str_replace( '_', '-', $shortcode );
            if ( ! wp_style_is( $handle, 'done' ) ) {
                echo self::output_inline_css_for_shortcode( $shortcode );
            }
        }, 20 );
    }
}

/**
 * Função helper para facilitar o uso
 */
function vs_enqueue_shortcode_css( $shortcodes = null ) {
    if ( $shortcodes ) {
        VS_CSS_Conditional_Loader::force_enqueue_shortcode_css( $shortcodes );
    } else {
        VS_CSS_Conditional_Loader::enqueue_conditional_css();
    }
}

/**
 * Função helper para output inline de CSS
 */
function vs_get_inline_css_for_shortcode( $shortcode ) {
    return VS_CSS_Conditional_Loader::output_inline_css_for_shortcode( $shortcode );
}

// Funções globais para compatibilidade com código existente
if ( ! function_exists( 'vs_is_page_builder_context' ) ) {
    function vs_is_page_builder_context() {
        return VS_CSS_Conditional_Loader::is_page_builder_context();
    }
}

if ( ! function_exists( 'vs_is_ajax_request' ) ) {
    function vs_is_ajax_request() {
        return VS_CSS_Conditional_Loader::is_ajax_request();
    }
}

if ( ! function_exists( 'vs_ensure_css_for_shortcode' ) ) {
    function vs_ensure_css_for_shortcode( $shortcode ) {
        return VS_CSS_Conditional_Loader::ensure_css_for_shortcode( $shortcode );
    }
}
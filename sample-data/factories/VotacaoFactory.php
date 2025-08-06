<?php
/**
 * Factory para criar votações
 * 
 * @package VotingSystem\SampleData\Factories
 */

defined( 'ABSPATH' ) || exit;

class VS_VotacaoFactory {
    
    public function create($config = []) {
        // Dados padrão
        $defaults = [
            'title' => 'Votação de Exemplo',
            'content' => 'Esta é uma votação de exemplo criada automaticamente.',
            'status' => 'aberta',
            'ano' => date('Y'),
            'evento' => null,
            'permitir_edicao' => false,
            'data_inicio' => 0,
            'data_fim' => 30
        ];
        
        $data = array_merge($defaults, $config);
        
        // Cria o post
        $post_data = [
            'post_title' => $data['title'],
            'post_content' => $data['content'],
            'post_type' => 'votacoes',
            'post_status' => 'publish',
            'post_author' => get_current_user_id()
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return false;
        }
        
        // Adiciona metadados
        update_post_meta($post_id, '_vs_ano', $data['ano']);
        update_post_meta($post_id, '_vs_status', $data['status']);
        
        // Gera código único
        $codigo_base = 'VOT-' . $data['ano'] . '-' . $post_id;
        $codigo = $this->generateUniqueCode($codigo_base, $post_id);
        update_post_meta($post_id, '_vs_codigo', $codigo);
        
        // Datas
        $data_inicio = $this->calculateDate($data['data_inicio']);
        $data_fim = $this->calculateDate($data['data_fim']);
        
        update_post_meta($post_id, '_vs_data_inicio', $data_inicio);
        update_post_meta($post_id, '_vs_data_fim', $data_fim);
        
        // Permitir edição
        if ($data['permitir_edicao']) {
            update_post_meta($post_id, 'vs_permitir_edicao', '1');
        }
        
        // Quantidade de votos (simulada)
        $qtd_votos = rand(15, 150);
        update_post_meta($post_id, '_vs_qtd_votos', $qtd_votos);
        
        // Associa ao evento se especificado
        if ($data['evento']) {
            $termo = get_term_by('slug', $data['evento'], 'eventos');
            if ($termo) {
                wp_set_post_terms($post_id, [$termo->term_id], 'eventos');
            }
        }
        
        return $post_id;
    }
    
    private function calculateDate($days_offset) {
        if ($days_offset === 0) {
            return date('Y-m-d');
        }
        
        $timestamp = strtotime($days_offset . ' days');
        return date('Y-m-d', $timestamp);
    }
    
    private function generateUniqueCode($codigo_base, $post_id) {
        global $wpdb;
        
        $codigo_final = $codigo_base;
        $contador = 0;

        while (true) {
            $existe = $wpdb->get_var($wpdb->prepare("
                SELECT post_id FROM $wpdb->postmeta 
                WHERE meta_key = '_vs_codigo' 
                  AND meta_value = %s
                  AND post_id != %d
                LIMIT 1
            ", $codigo_final, $post_id));

            if (!$existe) {
                break;
            }

            $contador++;
            $codigo_final = $codigo_base . '-' . $contador;
        }

        return $codigo_final;
    }
}
<?php
/**
 * Factory para criar respostas automáticas
 * 
 * @package VotingSystem\SampleData\Factories
 */

defined( 'ABSPATH' ) || exit;

class VS_ResponseFactory {
    
    /**
     * Array com 3 valores de respostas unificadas
     * Usado para 25% das respostas que serão unificadas
     */
    private $unified_responses = [
        'Aprovado pela maioria',
        'Necessita revisão',
        'Proposta aceita com ressalvas'
    ];
    
    /**
     * Templates de respostas por tipo de pergunta
     */
    private $response_templates = [
        'texto' => [
            'Concordo plenamente com a proposta apresentada e acredito que trará benefícios significativos.',
            'Tenho algumas ressalvas sobre os pontos mencionados, mas no geral apoio a iniciativa.',
            'Acredito que a proposta precisa de alguns ajustes antes de ser implementada.',
            'Excelente iniciativa! Sugiro apenas algumas melhorias nos detalhes técnicos.',
            'A proposta está bem estruturada e atende às necessidades atuais da organização.',
            'Penso que devemos considerar alternativas mais sustentáveis para este projeto.',
            'Apoio totalmente, mas sugiro um cronograma mais detalhado para implementação.',
            'A ideia é boa, porém os custos parecem elevados para o orçamento atual.',
            'Concordo com os objetivos, mas acredito que a metodologia pode ser aprimorada.',
            'Proposta interessante que pode trazer inovação para nossos processos.',
            'Sugiro uma análise mais aprofundada dos riscos envolvidos.',
            'Excelente trabalho! A proposta está clara e bem fundamentada.',
            'Acredito que precisamos de mais tempo para avaliar todos os aspectos.',
            'A proposta atende às expectativas e está alinhada com nossos valores.',
            'Sugiro incluir indicadores de performance para acompanhar os resultados.'
        ],
        'select' => [
            // Será escolhido aleatoriamente das opções disponíveis na pergunta
        ],
        'radio' => [
            // Será escolhido aleatoriamente das opções disponíveis na pergunta
        ],
        'checkbox' => [
            // Será escolhido aleatoriamente das opções disponíveis na pergunta (múltiplas seleções)
        ]
    ];
    
    /**
     * Cria uma resposta para uma pergunta específica
     */
    public function createResponse($question, $question_index = 0) {
        $tipo = $question['tipo'];
        $response = null;
        
        switch ($tipo) {
            case 'texto':
                $response = $this->createTextResponse();
                break;
                
            case 'select':
            case 'radio':
                $response = $this->createSingleChoiceResponse($question);
                break;
                
            case 'checkbox':
                $response = $this->createMultipleChoiceResponse($question);
                break;
                
            default:
                $response = 'Resposta não disponível';
        }
        
        return $response;
    }
    
    /**
     * Cria um conjunto completo de respostas para uma votação
     */
    public function createResponseSet($questions, $user_id, $votacao_id) {
        $respostas_detalhadas = [];
        $resposta_unificada = [];
        
        foreach ($questions as $index => $question) {
            // Gera a resposta individual
            $respostas_detalhadas[$index] = $this->createResponse($question, $index);
            
            // 25% de chance de ter resposta unificada
            if (rand(1, 100) <= 25) {
                $resposta_unificada[$index] = $this->getRandomUnifiedResponse();
            }
        }
        
        return [
            'respostas_detalhadas' => $respostas_detalhadas,
            'resposta_unificada' => $resposta_unificada
        ];
    }
    
    /**
     * Cria resposta para pergunta de texto livre
     */
    private function createTextResponse() {
        $templates = $this->response_templates['texto'];
        return $templates[array_rand($templates)];
    }
    
    /**
     * Cria resposta para pergunta de escolha única (select/radio)
     */
    private function createSingleChoiceResponse($question) {
        if (empty($question['options']) || !is_array($question['options'])) {
            return 'Opção não disponível';
        }
        
        return $question['options'][array_rand($question['options'])];
    }
    
    /**
     * Cria resposta para pergunta de múltipla escolha (checkbox)
     */
    private function createMultipleChoiceResponse($question) {
        if (empty($question['options']) || !is_array($question['options'])) {
            return [];
        }
        
        $options = $question['options'];
        $num_selections = rand(1, min(3, count($options))); // Seleciona entre 1 e 3 opções
        
        // Embaralha as opções e pega as primeiras N
        shuffle($options);
        return array_slice($options, 0, $num_selections);
    }
    
    /**
     * Retorna uma resposta unificada aleatória
     */
    private function getRandomUnifiedResponse() {
        return $this->unified_responses[array_rand($this->unified_responses)];
    }
    
    /**
     * Gera uma data de envio realista para a resposta
     */
    public function generateResponseDate($votacao_id) {
        $data_inicio = get_post_meta($votacao_id, '_vs_data_inicio', true);
        $data_fim = get_post_meta($votacao_id, '_vs_data_fim', true);
        
        if (empty($data_inicio) || empty($data_fim)) {
            return current_time('mysql');
        }
        
        $inicio_timestamp = strtotime($data_inicio);
        $fim_timestamp = strtotime($data_fim);
        
        // Gera uma data aleatória entre o início e fim da votação
        $random_timestamp = rand($inicio_timestamp, min($fim_timestamp, time()));
        
        return date('Y-m-d H:i:s', $random_timestamp);
    }
    
    /**
     * Determina se um usuário deve participar desta votação (taxa de participação realista)
     */
    public function shouldUserParticipate($user_id, $votacao_id) {
        // Taxa de participação entre 70% e 85%
        $participation_rate = rand(70, 85);
        
        // Usa uma combinação do user_id e votacao_id para gerar consistência
        $seed = $user_id + $votacao_id;
        srand($seed);
        $should_participate = rand(1, 100) <= $participation_rate;
        
        // Restaura o gerador de números aleatórios
        srand();
        
        return $should_participate;
    }
}
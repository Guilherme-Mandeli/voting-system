<?php
/**
 * Factory para criar perguntas
 * 
 * @package VotingSystem\SampleData\Factories
 */

defined( 'ABSPATH' ) || exit;

class VS_QuestionFactory {
    
    private $question_templates = [
        'texto' => [
            'labels' => [
                'Qual sua opinião sobre a proposta apresentada?',
                'Deixe seus comentários e sugestões:',
                'Descreva sua experiência com o tema:',
                'Que melhorias você sugere?'
            ]
        ],
        'select' => [
            'labels' => [
                'Escolha sua preferência:',
                'Selecione a melhor opção:',
                'Qual alternativa você prefere?',
                'Indique sua escolha:'
            ],
            'options' => [
                ['Opção A', 'Opção B', 'Opção C'],
                ['Primeira alternativa', 'Segunda alternativa', 'Terceira alternativa', 'Quarta alternativa'],
                ['Sim', 'Não', 'Talvez', 'Preciso de mais informações'],
                ['Concordo totalmente', 'Concordo parcialmente', 'Discordo', 'Não tenho opinião']
            ]
        ],
        'radio' => [
            'labels' => [
                'Selecione sua posição:',
                'Qual sua avaliação?',
                'Como você classifica?',
                'Qual seu nível de concordância?'
            ],
            'options' => [
                ['Excelente', 'Bom', 'Regular', 'Ruim', 'Péssimo'],
                ['Muito importante', 'Importante', 'Pouco importante', 'Irrelevante'],
                ['Aprovo', 'Aprovo com ressalvas', 'Reprovo', 'Não sei opinar'],
                ['Sempre', 'Frequentemente', 'Às vezes', 'Raramente', 'Nunca']
            ]
        ],
        'checkbox' => [
            'labels' => [
                'Marque todas as opções que se aplicam:',
                'Selecione os itens de seu interesse:',
                'Quais aspectos são importantes para você?',
                'Indique todas as alternativas relevantes:'
            ],
            'options' => [
                ['Transparência', 'Eficiência', 'Participação', 'Inovação', 'Sustentabilidade', 'Qualidade'],
                ['Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'],
                ['Comunicação', 'Gestão', 'Tecnologia', 'Recursos Humanos', 'Financeiro', 'Marketing'],
                ['Manhã', 'Tarde', 'Noite', 'Madrugada']
            ]
        ]
    ];
    
    /**
     * Cria um conjunto de perguntas
     * 
     * @param array $types Tipos de perguntas a serem criadas
     * @param int|null $votacao_id ID da votação para associar as perguntas
     * @return array Array de perguntas criadas
     */
    public function createSet($types = ['texto', 'select', 'radio', 'checkbox'], $votacao_id = null) {
        $questions = [];
        
        foreach ($types as $index => $type) {
            $questions[] = $this->createQuestion($type, $index, $votacao_id);
        }
        
        return $questions;
    }
    
    /**
     * Cria uma pergunta individual
     * 
     * @param string $type Tipo da pergunta
     * @param int $index Índice da pergunta
     * @param int|null $votacao_id ID da votação
     * @return array Dados da pergunta criada
     */
    private function createQuestion($type, $index = 0, $votacao_id = null) {
        $template = $this->question_templates[$type];
        $label_index = $index % count($template['labels']);
        
        // Buscar informações do evento se votacao_id for fornecido
        $event_info = $this->getEventInfoForVotacao($votacao_id);
        
        // Criar estrutura de imported_answers com informações do evento
        $imported_answers_structure = [
            'questions' => []
        ];
        
        // Se há informações do evento, criar dados de exemplo
        if ($event_info['event_id']) {
            $sample_answers = $this->generateSampleImportedAnswers($type, $template, $index);
            $imported_answers_structure['questions'][] = [
                'question_source' => $template['labels'][$label_index],
                'question_index' => $index + 1,
                'vote_id' => $votacao_id,
                'vote_title' => get_the_title($votacao_id),
                'event_id' => $event_info['event_id'],
                'event_title' => $event_info['event_title'],
                'event_slug' => $event_info['event_slug'],
                'total_votos' => array_sum(array_column($sample_answers, 'qtd_votos')),
                'imported_answers' => $sample_answers
            ];
        }
        
        $question = [
            'label' => $template['labels'][$label_index],
            'tipo' => $type,
            'obrigatoria' => rand(0, 1) === 1,
            'unificada' => '',
            'imported_vote_id' => $votacao_id ?: 0,
            'imported_answers' => json_encode($imported_answers_structure, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ];
        
        // Adiciona opções para tipos que precisam
        if (in_array($type, ['select', 'radio', 'checkbox'])) {
            $options_index = $index % count($template['options']);
            $question['options'] = $template['options'][$options_index];
        } else {
            $question['options'] = [];
        }
        
        return $question;
    }
    
    /**
     * Obtém informações do evento associado à votação
     * 
     * @param int|null $votacao_id ID da votação
     * @return array Informações do evento
     */
    private function getEventInfoForVotacao($votacao_id) {
        if (!$votacao_id) {
            return ['event_id' => null, 'event_title' => '', 'event_slug' => ''];
        }
        
        $eventos = wp_get_post_terms($votacao_id, 'eventos');
        if (!empty($eventos) && !is_wp_error($eventos)) {
            return [
                'event_id' => $eventos[0]->term_id,
                'event_title' => $eventos[0]->name,
                'event_slug' => $eventos[0]->slug
            ];
        }
        
        return ['event_id' => null, 'event_title' => '', 'event_slug' => ''];
    }
    
    /**
     * Gera respostas de exemplo para imported_answers
     * 
     * @param string $type Tipo da pergunta
     * @param array $template Template da pergunta
     * @param int $index Índice para variação das opções
     * @return array Array de respostas de exemplo
     */
    private function generateSampleImportedAnswers($type, $template, $index = 0) {
        $sample_answers = [];
        
        switch ($type) {
            case 'texto':
                $responses = [
                    'Concordo plenamente com a proposta',
                    'Tenho algumas ressalvas sobre os pontos mencionados',
                    'Acredito que precisa de ajustes',
                    'Proposta interessante, mas precisa de mais detalhes',
                    'Não concordo com a abordagem apresentada'
                ];
                
                // Seleciona algumas respostas aleatórias
                $selected_responses = array_slice($responses, 0, rand(3, 5));
                foreach ($selected_responses as $response) {
                    $sample_answers[] = [
                        'value' => $response,
                        'value_unificada' => 'Feedback Geral',
                        'qtd_votos' => rand(5, 25)
                    ];
                }
                break;
                
            case 'select':
            case 'radio':
                if (isset($template['options'])) {
                    $options_index = $index % count($template['options']);
                    foreach ($template['options'][$options_index] as $option) {
                        $sample_answers[] = [
                            'value' => $option,
                            'value_unificada' => $option,
                            'qtd_votos' => rand(3, 20)
                        ];
                    }
                }
                break;
                
            case 'checkbox':
                if (isset($template['options'])) {
                    $options_index = $index % count($template['options']);
                    foreach ($template['options'][$options_index] as $option) {
                        // Para checkbox, nem todas as opções precisam ter votos
                        if (rand(0, 1)) {
                            $sample_answers[] = [
                                'value' => $option,
                                'value_unificada' => $option,
                                'qtd_votos' => rand(2, 15)
                            ];
                        }
                    }
                }
                break;
        }
        
        return $sample_answers;
    }
}

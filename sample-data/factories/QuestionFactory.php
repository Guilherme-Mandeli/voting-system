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
    
    public function createSet($types = ['texto', 'select', 'radio', 'checkbox']) {
        $questions = [];
        
        foreach ($types as $index => $type) {
            $questions[] = $this->createQuestion($type, $index);
        }
        
        return $questions;
    }
    
    private function createQuestion($type, $index = 0) {
        $template = $this->question_templates[$type];
        $label_index = $index % count($template['labels']);
        
        $question = [
            'label' => $template['labels'][$label_index],
            'tipo' => $type,
            'obrigatoria' => rand(0, 1) === 1, // 50% chance de ser obrigatória
            'unificada' => '', // Não preencher conforme solicitado
            'imported_vote_id' => 0,
            'imported_answers' => wp_json_encode(['perguntas' => []])
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
}
/**
 * SISTEMA DE FEATURE FLAG PARA CONTROLE DE MERGE
 * 
 * Este objeto global controla o comportamento da nova lógica de importação,
 * permitindo ativação/desativação segura e fallback para comportamento legacy.
 */
window.VS_IMPORT_MERGE_STRATEGY = {
    /**
     * Controla se a nova lógica de merge está ativa
     * @type {boolean}
     */
    enabled: true,
    
    /**
     * Define se deve usar comportamento antigo em caso de erro
     * @type {boolean}
     */
    fallbackToLegacy: true,
    
    /**
     * Ativa logs detalhados para troubleshooting
     * @type {boolean}
     */
    debugMode: true,
    
    /**
     * Configurações avançadas para controle fino
     * @type {Object}
     */
    advanced: {
        /**
         * Timeout em ms para operações de merge (0 = sem timeout)
         * @type {number}
         */
        mergeTimeout: 5000,
        
        /**
         * Máximo de tentativas em caso de erro
         * @type {number}
         */
        maxRetries: 2,
        
        /**
         * Ativa validação extra de dados
         * @type {boolean}
         */
        strictValidation: false,
        
        /**
         * Ativa métricas de performance
         * @type {boolean}
         */
        performanceMetrics: false
    },
    
    /**
     * Métodos utilitários para controle do feature flag
     */
    utils: {
        /**
         * Ativa modo debug temporariamente
         * @param {number} durationMs - Duração em milissegundos (padrão: 30s)
         */
        enableDebugTemporarily: function(durationMs = 30000) {
            const originalDebugMode = window.VS_IMPORT_MERGE_STRATEGY.debugMode;
            window.VS_IMPORT_MERGE_STRATEGY.debugMode = true;
            console.log('🐛 Debug mode ativado temporariamente por', durationMs + 'ms');
            
            setTimeout(() => {
                window.VS_IMPORT_MERGE_STRATEGY.debugMode = originalDebugMode;
                console.log('🐛 Debug mode restaurado para:', originalDebugMode);
            }, durationMs);
        },
        
        /**
         * Força fallback para legacy temporariamente
         * @param {number} durationMs - Duração em milissegundos (padrão: 60s)
         */
        forceLegacyTemporarily: function(durationMs = 60000) {
            const originalEnabled = window.VS_IMPORT_MERGE_STRATEGY.enabled;
            window.VS_IMPORT_MERGE_STRATEGY.enabled = false;
            console.warn('⚠️ Nova lógica desabilitada temporariamente por', durationMs + 'ms');
            
            setTimeout(() => {
                window.VS_IMPORT_MERGE_STRATEGY.enabled = originalEnabled;
                console.log('✅ Nova lógica restaurada para:', originalEnabled);
            }, durationMs);
        },
        
        /**
         * Obtém status atual do sistema
         * @returns {Object} Status detalhado
         */
        getStatus: function() {
            return {
                enabled: window.VS_IMPORT_MERGE_STRATEGY.enabled,
                fallbackToLegacy: window.VS_IMPORT_MERGE_STRATEGY.fallbackToLegacy,
                debugMode: window.VS_IMPORT_MERGE_STRATEGY.debugMode,
                advanced: { ...window.VS_IMPORT_MERGE_STRATEGY.advanced },
                timestamp: new Date().toISOString()
            };
        }
    }
};

// Log inicial do sistema
if (window.VS_IMPORT_MERGE_STRATEGY.debugMode) {
    console.log('🚀 VS Import Merge Strategy inicializado:', window.VS_IMPORT_MERGE_STRATEGY.utils.getStatus());
}

(function($) {
    'use strict';

    window.VSAdmin = window.VSAdmin || {};
    window.VSAdmin.ImportedAnswers = {
        
        currentQuestion: null,
        isUpdating: false,
        updateTimeout: null,

        init: function() {
            this.bindEvents();
            this.initializeExistingAnswers();
        },

        bindEvents: function() {
            // Adicionar opção selecionada
            $(document).on('click', '.vs-add-selected', this.addSelected.bind(this));
            
            // Remover opção
            $(document).on('click', '.vs-remove-option', this.removeOption.bind(this));
            
            // Selecionar todas as respostas
            $(document).on('change', '.vs-select-all-answers', this.selectAllAnswers.bind(this));
            
            // Remover seleção
            $(document).on('click', '.vs-remover-selecao', this.removeSelection.bind(this));
            
            // Ordenar tabela
            $(document).on('click', '.vs-ordenar-valor, .vs-ordenar-votos', this.handleSort.bind(this));
            
            // Importar múltiplas perguntas selecionadas
            $(document).on('click', '.vs-import-selected', this.initQuestionImport.bind(this));
            
            // Eventos personalizados
            $(document).on('vs:question-selected', this.handleQuestionSelected.bind(this));
            $(document).on('vs:update-imported-table', this.handleUpdateTable.bind(this));

            // Importar pergunta individual
            $(document).on('click', '.vs-importar-pergunta', this.importSingleQuestion.bind(this));
        },

        handleQuestionSelected: function(event, $questionContainer) {
            this.setCurrentQuestion($questionContainer);
        },

        handleUpdateTable: function(event, $questionContainer) {
            this.setCurrentQuestion($questionContainer);
            this.updateTable();
        },

        setCurrentQuestion: function($question) {
            this.currentQuestion = $question;
            // Atualizar variável global para compatibilidade
            window.currentQuestion = $question;
        },

        getCurrentQuestion: function() {
            return this.currentQuestion;
        },

        updateTable: function() {            
            if (!this.currentQuestion || !this.currentQuestion.length) {
                console.warn('currentQuestion não está definido ou é inválido');
                return;
            }

            const $jsonInput = this.currentQuestion.find('.vs-imported-answers');
            if (!$jsonInput.length) {
                console.warn('Campo .vs-imported-answers não encontrado');
                return;
            }

            const $tbody = this.currentQuestion.find('.vs-imported-column tbody');
            if (!$tbody.length) {
                console.warn('Tabela .vs-imported-column tbody não encontrada');
                return;
            }

            // Prevenir múltiplas execuções simultâneas
            if (this.isUpdating) {
                return;
            }

            // Implementar debounce para evitar chamadas muito próximas
            if (this.updateTimeout) {
                clearTimeout(this.updateTimeout);
            }

            this.updateTimeout = setTimeout(() => {
                this._executeUpdateTable();
            }, 50);
        },

        // Função para buscar informações do evento via AJAX
        fetchEventInfo: function(voteId) {
            return new Promise((resolve, reject) => {
                if (!voteId) {
                    reject('Vote ID não fornecido');
                    return;
                }

                $.ajax({
                    url: vs_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'vs_get_event_info',
                        vote_id: voteId,
                        nonce: vs_admin.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            resolve({
                                event_name: response.data.event_name || 'Evento sem nome',
                                vote_id: voteId
                            });
                        } else {
                            reject('Erro ao buscar informações do evento');
                        }
                    },
                    error: function() {
                        reject('Erro na requisição AJAX');
                    }
                });
            });
        },

        // Função para extrair vote_ids únicos dos dados JSON
        extractUniqueVoteIds: function(data) {
            const voteIds = new Set();
            
            if (data && data.questions && Array.isArray(data.questions)) {
                data.questions.forEach(pergunta => {
                    if (pergunta.vote_id) {
                        voteIds.add(pergunta.vote_id);
                    }
                });
            }
            
            return Array.from(voteIds);
        },

        // Função para renderizar o parágrafo informativo
        renderEventSummary: function(eventInfo, totalAnswers) {
            const $container = this.currentQuestion.find('.vs-imported-column');
            const $existingSummary = $container.find('.vs-event-summary');
            
            // Remover parágrafo existente se houver
            if ($existingSummary.length) {
                $existingSummary.remove();
            }
            
            // Criar novo parágrafo informativo
            const $summaryParagraph = $('<div>', {
                class: 'vs-event-summary',
            });
            
            let summaryText = '';
            if (eventInfo.length === 1) {
                // Um único evento
                const event = eventInfo[0];
                summaryText = `<strong>Evento:</strong> ${event.event_name} | <strong>Votação:</strong> ${event.vote_title} | <strong>Respostas:</strong> ${totalAnswers}`;
            } else if (eventInfo.length > 1) {
                // Múltiplos eventos
                const eventNames = eventInfo.map(info => info.event_name).join(', ');
                summaryText = `<strong>Eventos:</strong> ${eventNames} | <strong>Respostas:</strong> ${totalAnswers}`;
            } else {
                // Nenhum evento encontrado
                summaryText = `<strong>Respostas exibidas:</strong> ${totalAnswers}`;
            }
            
            $summaryParagraph.html(summaryText);
            
            // Inserir antes da tabela
            $container.find('table').before($summaryParagraph);
        },

        // Função para extrair informações dos eventos dos dados JSON
        extractEventInfo: function(data) {
            const eventMap = new Map();
            
            if (data && data.questions && Array.isArray(data.questions)) {
                data.questions.forEach(pergunta => {
                    const voteId = pergunta.vote_id;
                    
                    if (voteId) {
                        const eventInfo = {
                            event_id: pergunta.event_id || null,
                            event_name: pergunta.event_title || 'Evento sem nome',
                            event_slug: pergunta.event_slug || null,
                            vote_id: voteId,
                            vote_title: pergunta.vote_title || 'Votação sem título'
                        };
                        
                        const eventKey = eventInfo.event_id || voteId;
                        if (!eventMap.has(eventKey)) {
                            eventMap.set(eventKey, eventInfo);
                        }
                    }
                });
            }
            
            return Array.from(eventMap.values());
        },

        _executeUpdateTable: function() {
            this.isUpdating = true;
            
            try {                
                const $tbody = this.currentQuestion.find('.vs-imported-column tbody');
                const $importedAnswersField = this.currentQuestion.find('.vs-imported-answers');
                const jsonData = $importedAnswersField.val();
                
                if (!jsonData || jsonData.trim() === '' || jsonData === '{}') {
                    $tbody.empty();
                    
                    // Remover parágrafo de resumo se não há perguntas
                    this.currentQuestion.find('.vs-event-summary').remove();
                    
                    $tbody.append($('<tr>').append(
                        $('<td>', { 
                            colspan: 5, 
                            style: 'text-align: center; padding: 20px; color: #666;' 
                        }).text('Nenhuma resposta foi importada.')
                    ));
                    this.isUpdating = false;
                    return;
                }
                
                let data;
                try {
                    data = JSON.parse(jsonData);
                } catch (parseError) {
                    console.error('Erro ao fazer parse do JSON:', parseError);
                    this.currentQuestion.find('.vs-event-summary').remove();
                    $tbody.append($('<tr>').append(
                        $('<td>', { 
                            colspan: 5, 
                            style: 'text-align: center; padding: 20px; color: #d63638;' 
                        }).text('Erro: Dados JSON inválidos.')
                    ));
                    this.isUpdating = false;
                    return;
                }
                
                if (!data.questions || !Array.isArray(data.questions) || data.questions.length === 0) {
                    $tbody.empty();
                    
                    // Remover parágrafo de resumo se não há perguntas
                    this.currentQuestion.find('.vs-event-summary').remove();
                    
                    $tbody.append($('<tr>').append(
                        $('<td>', { 
                            colspan: 5, 
                            style: 'text-align: center; padding: 20px; color: #666;' 
                        }).text('Nenhuma resposta foi importada.')
                    ));
                    this.isUpdating = false;
                    return;
                }

                // Extrair informações dos eventos diretamente dos dados JSON
                const eventInfoArray = this.extractEventInfo(data);

                // Processar dados da tabela diretamente
                this.processTableData(data, eventInfoArray);

            } catch (error) {
                console.error('Erro em _executeUpdateTable:', error);
                this.isUpdating = false;
            }
        },

        updateCheckboxStates: function() {
            const $container = $('.vs-imported-column');
            if (!$container.length) return;
            
            // Obter valores reais das opções existentes (mesma lógica da _executeUpdateTable)
            const existingValues = [];
            this.currentQuestion.find('.vs-valor-real').each(function() {
                const value = $(this).val();
                if (value && value.trim() !== '') {
                    existingValues.push(value.trim());
                }
            });
            
            $container.find('.vs-select-answer').each(function() {
                const $checkbox = $(this);
                const valor = $checkbox.data('valor');
                const isExisting = existingValues.includes(valor);
                
                if (isExisting) {
                    // Item ainda existe como vs-option-item: desabilitar e manter checked
                    $checkbox.prop('disabled', true).prop('checked', true);
                    $checkbox.attr('title', 'Esta resposta já foi importada');
                    $checkbox.addClass('vs-checkbox-disabled');
                } else {
                    // Item foi removido: habilitar e desmarcar
                    $checkbox.prop('disabled', false).prop('checked', false);
                    $checkbox.removeAttr('title');
                    $checkbox.removeClass('vs-checkbox-disabled');
                }
            });
        },

        // Processar os dados da tabela
        processTableData: function(data, eventInfoArray) {
            try {
                const $tbody = this.currentQuestion.find('.vs-imported-column tbody');
                
                // Limpar a tabela antes de processar novos dados
                $tbody.empty();
                
                // Verificar se há duplicatas nos dados JSON
                const allAnswers = [];
                data.questions.forEach(pergunta => {
                    if (pergunta.imported_answers) {
                        pergunta.imported_answers.forEach(resposta => {
                            allAnswers.push({
                                value: resposta.value_unificada || resposta.value,
                                qtd_votos: resposta.qtd_votos
                            });
                        });
                    }
                });

                // Obter valores reais das opções existentes
                const existingValues = [];
                this.currentQuestion.find('.vs-valor-real').each(function() {
                    const value = $(this).val();
                    if (value && value.trim() !== '') {
                        existingValues.push(value.trim());
                    }
                });

                let rowNumber = 1;
                let hasAnswers = false;
                let processedAnswers = new Set(); // Para evitar duplicatas na renderização
                let totalDisplayedAnswers = 0;

                data.questions.forEach((pergunta, perguntaIndex) => {                    
                    if (pergunta.imported_answers && Array.isArray(pergunta.imported_answers) && pergunta.imported_answers.length > 0) {
                        
                        pergunta.imported_answers.forEach((resposta, respostaIndex) => {
                            const valorExibir = resposta.value_unificada || resposta.value;
                            const qtdVotos = parseInt(resposta.qtd_votos || 0);
                            const questionSource = pergunta.question_source || 'Fonte desconhecida';
                            
                            // Criar chave única para detectar duplicatas
                            const uniqueKey = `${valorExibir}_${qtdVotos}_${questionSource}`;
                            
                            if (processedAnswers.has(uniqueKey)) {
                                return; // Pular esta resposta duplicada
                            }
                            
                            processedAnswers.add(uniqueKey);
                            hasAnswers = true;
                            totalDisplayedAnswers++;
                            
                            // Verificar se já existe nas opções
                            const isExisting = existingValues.includes(valorExibir);
                            
                            const $row = $('<tr>');
                            
                            // Coluna do número (primeira coluna)
                            $row.append($('<td>').text(rowNumber));
                            
                            // Coluna de seleção (segunda coluna)
                            const $checkboxCell = $('<td>');
                            const $checkbox = $('<input>', {
                                type: 'checkbox',
                                class: 'vs-select-answer',
                                'data-valor': resposta.value || valorExibir,
                                'data-valor-unificado': resposta.value_unificada || '',
                                value: valorExibir,
                                disabled: isExisting, // Desabilitar se já existe
                                checked: isExisting   // Marcar como checked se já existe
                            });

                            // Adicionar classe especial e título para checkboxes desabilitados
                            if (isExisting) {
                                $checkbox.addClass('vs-checkbox-disabled');
                                $checkbox.attr('title', 'Esta resposta já foi importada');
                                $checkboxCell.addClass('vs-existing-item');
                            }

                            $checkboxCell.append($checkbox);
                            $row.append($checkboxCell);
                            
                            // Coluna do valor (terceira coluna)
                            $row.append($('<td>').text(valorExibir));
                            
                            // Coluna da quantidade de votos (quarta coluna)
                            $row.append($('<td>').text(qtdVotos));
                            
                            // Coluna da fonte da pergunta (quinta coluna)
                            $row.append($('<td>').text(questionSource));
                            
                            $tbody.append($row);
                            rowNumber++;
                        });
                    }
                });

                // Se não há respostas válidas, mostrar mensagem
                if (!hasAnswers) {
                    $tbody.append($('<tr>').append(
                        $('<td>', { 
                            colspan: 5, 
                            style: 'text-align: center; padding: 20px; color: #666;' 
                        }).text('Nenhuma resposta foi importada.')
                    ));
                    // Remover parágrafo de resumo se não há perguntas
                    this.currentQuestion.find('.vs-event-summary').remove();
                } else {
                    // Renderizar parágrafo informativo com informações dos eventos
                    if (eventInfoArray.length > 0) {
                        this.renderEventSummary(eventInfoArray, totalDisplayedAnswers);
                    } else {
                        // Se não há informações de evento, mostrar apenas a quantidade
                        this.renderEventSummary([], totalDisplayedAnswers);
                    }
                }
                
            } catch (error) {
                console.error('Erro ao processar dados da tabela:', error);
            } finally {
                this.isUpdating = false;
            }
        },

        addSelected: function(event) {
            const $container = $(event.target).closest('.vs-columns-container');
            const $questionContainer = $container.closest('.vs-pergunta');
            const questionIndex = $questionContainer.find('[name*="[label]"]').attr('name').match(/\[(\d+)\]/)[1];
            const $optionsContainer = $container.find('.vs-options-column .vs-options');
            const $importedAnswersField = $questionContainer.find('.vs-imported-answers');
            
            // Obter dados atuais do imported_answers
            let importedAnswersData;
            try {
                importedAnswersData = JSON.parse($importedAnswersField.val() || '{}');
                
                // Preservar manual_items e imported_items existentes
                if (!importedAnswersData.manual_items) {
                    importedAnswersData.manual_items = [];
                }
                if (!importedAnswersData.imported_items) {
                    importedAnswersData.imported_items = [];
                }
                if (!importedAnswersData.questions) {
                    importedAnswersData.questions = [];
                }
                
                // Garantir que sejam arrays válidos
                if (!Array.isArray(importedAnswersData.manual_items)) {
                    importedAnswersData.manual_items = [];
                }
                if (!Array.isArray(importedAnswersData.imported_items)) {
                    importedAnswersData.imported_items = [];
                }
                if (!Array.isArray(importedAnswersData.questions)) {
                    importedAnswersData.questions = [];
                }
            } catch (e) {
                importedAnswersData = { questions: [], manual_items: [], imported_items: [] };
            }
            
            // Inicializar arrays se não existirem
            if (!importedAnswersData.manual_items) importedAnswersData.manual_items = [];
            if (!importedAnswersData.imported_items) importedAnswersData.imported_items = [];
            if (!importedAnswersData.questions) importedAnswersData.questions = [];
            
            // Obter respostas selecionadas da tabela
            const $selectedAnswers = $container.find('.vs-select-answer:checked:not(:disabled)');
            
            $selectedAnswers.each(function() {
                const $tr = $(this).closest('tr');
                const tableDisplayValue = $tr.find('td:eq(2)').text().trim();
                const originalValue = $(this).data('valor');
                const unifiedValue = $(this).data('valor-unificado');
                const realValue = originalValue;
                const visualValue = unifiedValue || tableDisplayValue;
                const sourceQuestion = $tr.find('td:eq(4)').text();
                
                // Verificar se já existe uma opção com este valor real E visual
                let isDuplicate = false;
                $optionsContainer.find('.vs-option-item').each(function() {
                    const existingRealValue = $(this).find('.vs-valor-real').val();
                    const existingVisualValue = $(this).find('input[type="text"]').val();
                    
                    // Considerar duplicata apenas se AMBOS os valores forem iguais
                    if (existingRealValue === realValue && existingVisualValue === visualValue) {
                        isDuplicate = true;
                        return false; // break
                    }
                });
                
                // Se for duplicata, pular esta opção
                if (isDuplicate) {
                    return true; // continue do loop
                }
                
                // Obter o índice real baseado na posição DOM atual
                const currentOptionIndex = $optionsContainer.find('.vs-option-item').length;
                
                // Criar nova opção
                const $optionItem = $('<div>', {
                    class: 'vs-option-item imported_question',
                    style: 'margin-bottom: 5px;'
                });
                
                const $textInput = $('<input>', {
                    type: 'text',
                    name: `vs_questions[${questionIndex}][options][]`,
                    value: visualValue,
                    style: 'width: 90%;',
                    placeholder: `Opção ${currentOptionIndex + 1}`
                });
                
                const $hiddenInput = $('<input>', {
                    type: 'hidden',
                    name: `vs_questions[${questionIndex}][valores_reais][${currentOptionIndex}]`,
                    class: 'vs-valor-real',
                    value: realValue
                });
                
                const $valorRealTexto = $('<span>', {
                    class: 'vs-valor-real-texto',
                    css: { fontSize: '12px', color: '#666', marginLeft: '10px' },
                    text: realValue
                });
                
                const $removeButton = $('<button>', {
                    type: 'button',
                    class: 'button button-small vs-remove-option',
                    text: 'Remover'
                });
                
                // Montar estrutura unificada
                $optionItem.append($textInput, $hiddenInput, $valorRealTexto, $removeButton);
                
                // Inserir antes do botão "Adicionar Opção"
                $optionsContainer.find('.vs-add-option').before($optionItem);
                
                // Adicionar ao array de itens importados usando o índice correto
                importedAnswersData.imported_items.push(currentOptionIndex);
            });
            
            // Atualizar o campo imported_answers
            $importedAnswersField.val(JSON.stringify(importedAnswersData));
            
            // Desmarcar apenas checkboxes que foram processados com sucesso
            $selectedAnswers.each(function() {
                const $checkbox = $(this);
                const realValue = $checkbox.data('valor');
                const visualValue = $checkbox.closest('tr').find('td:eq(2)').text();
                let wasAdded = false;
                
                // Verificar se foi realmente adicionado
                $optionsContainer.find('.vs-option-item').each(function() {
                    const existingReal = $(this).find('.vs-valor-real').val();
                    const existingVisual = $(this).find('input[type="text"]').val();
                    if (existingReal === realValue && existingVisual === visualValue) {
                        wasAdded = true;
                        return false;
                    }
                });
            });
            
            // Desmarcar o checkbox "Selecionar todos" após adicionar itens selecionados
            $container.find('.vs-select-all-answers').prop('checked', false);

            // Atualizar estados dos checkboxes após adicionar itens
            this.updateCheckboxStates();
        },

        // Atualizar checkboxes baseado nas opções existentes
        updateCheckboxesBasedOnExistingOptions: function($container) {
            const $questionContainer = $container.closest('.vs-pergunta');
            
            // Obter todos os valores reais das opções existentes
            const existingValues = [];
            $questionContainer.find('.vs-valor-real').each(function() {
                const value = $(this).val();
                if (value && value.trim() !== '') {
                    existingValues.push(value.trim());
                }
            });
            
            // Verificar todos os checkboxes da tabela
            $container.find('.vs-select-answer').each(function() {
                const $checkbox = $(this);
                const valorResposta = $checkbox.data('valor') || '';
                const isExistingOption = existingValues.includes(valorResposta.trim());
                
                // APENAS marcar, nunca desmarcar checkboxes já selecionados pelo usuário
                if (isExistingOption && !$checkbox.prop('checked')) {
                    $checkbox.prop('checked', true);
                }
            });
        },

        removeOption: function(event) {
            const $optionItem = $(event.target).closest('.vs-option-item');
            const $questionContainer = $optionItem.closest('.vs-pergunta');
            const $container = $questionContainer.find('.vs-columns-container');
            const $importedAnswersField = $questionContainer.find('.vs-imported-answers');
            
            // Verificar se é uma opção importada antes de remover
            const isImportedQuestion = $optionItem.hasClass('imported_question');
            
            // Se for uma opção importada, obter os valores para desmarcar o checkbox correspondente
            let valorReal = null;
            let voteId = null;
            let questionIndex = null;
            
            if (isImportedQuestion) {
                valorReal = $optionItem.find('.vs-valor-real').val();
                
                // Obter dados para identificar a pergunta de origem
                try {
                    const importedData = JSON.parse($importedAnswersField.val() || '{}');
                    if (importedData.questions) {
                        // Encontrar a pergunta que contém esta resposta
                        for (const pergunta of importedData.questions) {
                            const foundAnswer = pergunta.imported_answers.find(answer => 
                                answer.value === valorReal || answer.value_unificada === valorReal
                            );
                            if (foundAnswer) {
                                voteId = pergunta.vote_id;
                                questionIndex = pergunta.question_index - 1; // Ajustar para índice base 0
                                break;
                            }
                        }
                    }
                } catch (e) {
                    console.error('Erro ao processar dados importados:', e);
                }
            }
            
            // Obter o índice da opção que está sendo removida
            const optionIndex = $optionItem.index();
            
            // Atualizar arrays manual_items e imported_items
            let importedAnswersData;
            try {
                importedAnswersData = JSON.parse($importedAnswersField.val() || '{}');
            } catch (e) {
                importedAnswersData = { questions: [], manual_items: [], imported_items: [] };
            }
            
            // Inicializar arrays se não existirem
            if (!importedAnswersData.manual_items) importedAnswersData.manual_items = [];
            if (!importedAnswersData.imported_items) importedAnswersData.imported_items = [];
            
            // Remover o índice dos arrays
            importedAnswersData.manual_items = importedAnswersData.manual_items.filter(index => index !== optionIndex);
            importedAnswersData.imported_items = importedAnswersData.imported_items.filter(index => index !== optionIndex);
            
            // Atualizar o campo imported_answers
            $importedAnswersField.val(JSON.stringify(importedAnswersData));
            
            // Remover a opção
            $optionItem.remove();

            // Atualizar estado dos checkboxes após remoção
            const self = this;
            setTimeout(function() {
                self.updateCheckboxStates();
            }, 100);
            
            // Sincronizar com checkboxes do modal
            if (isImportedQuestion && voteId && questionIndex !== null) {
                // Atualizar metadados de seleções
                try {
                    const importedData = JSON.parse($importedAnswersField.val() || '{}');
                    if (importedData.selected_questions && importedData.selected_questions[voteId]) {
                        // Verificar se ainda existem outras respostas desta pergunta
                        const remainingAnswers = importedData.questions.filter(p => 
                            p.vote_id === voteId && p.question_index === (questionIndex + 1)
                        );
                        
                        if (remainingAnswers.length === 0 || 
                            (remainingAnswers.length === 1 && remainingAnswers[0].imported_answers.length === 0)) {
                            // Remover pergunta dos metadados se não há mais respostas
                            importedData.selected_questions[voteId] = 
                                importedData.selected_questions[voteId].filter(idx => idx !== questionIndex);
                            
                            // Se não há mais perguntas selecionadas desta votação, remover completamente
                            if (importedData.selected_questions[voteId].length === 0) {
                                delete importedData.selected_questions[voteId];
                            }
                            
                            // Atualizar campo
                            $importedAnswersField.val(JSON.stringify(importedData));
                        }
                    }
                } catch (e) {
                    console.error('Erro ao atualizar metadados:', e);
                }
                
                // Desmarcar checkbox correspondente se o modal estiver aberto
                const $modal = $(`.vs-modal:visible`);
                if ($modal.length) {
                    const $checkbox = $modal.find(
                        `.vs-select-question[data-votacao-id="${voteId}"][data-question-index="${questionIndex}"]`
                    );
                    if ($checkbox.length) {
                        $checkbox.prop('checked', false);
                        
                        // Atualizar estado do "Selecionar todas"
                        const totalCheckboxes = $modal.find('.vs-select-question').length;
                        const checkedCheckboxes = $modal.find('.vs-select-question:checked').length;
                        $modal.find('.vs-select-all-questions').prop('checked', 
                            totalCheckboxes > 0 && checkedCheckboxes === totalCheckboxes
                        );
                        
                        // Atualizar botão de importação
                        if (window.VSAdmin.VotingModal) {
                            window.VSAdmin.VotingModal.updateImportButton($modal);
                        }
                    }
                }
                
                // Sincronizar checkboxes após remoção
                this.updateCheckboxStates();
            }
        },

        selectAllAnswers: function(event) {
            const isChecked = $(event.target).prop('checked');
            // Selecionar apenas checkboxes habilitados
            $(event.target).closest('table').find('.vs-select-answer:not(:disabled)').prop('checked', isChecked);
        },

        removeSelection: function(event) {
            const $container = $(event.target).closest('.vs-pergunta');
            $container.find('.vs-imported-answers')
                .val('')
                .attr('vote-id-list', '');
            $container.find('.vs-votacao-selecionada').empty();
        },

        handleSort: function(event) {
            const isValue = $(event.target).hasClass('vs-ordenar-valor');
            const $tbody = $(event.target).closest('.vs-imported-column').find('table tbody');
            const rows = $tbody.find('tr').get();
            const order = $(event.target).data('ordem') || 'asc';

            rows.sort((a, b) => {
                const aVal = isValue ? 
                    $(a).find('td:eq(2)').text() : 
                    parseInt($(a).find('td:eq(3)').text());
                const bVal = isValue ? 
                    $(b).find('td:eq(2)').text() : 
                    parseInt($(b).find('td:eq(3)').text());

                return order === 'asc' ? 
                    (aVal > bVal ? 1 : -1) : 
                    (aVal < bVal ? 1 : -1);
            });

            $(event.target).data('ordem', order === 'asc' ? 'desc' : 'asc');
            $(event.target).text($(event.target).text().replace(/[▲▼]/, order === 'asc' ? '▼' : '▲'));

            $tbody.empty().append(rows);
        },

        initializeExistingAnswers: function() {
            
            const $camposImportedVote = $('.vs-tipo-campo');
            
            $camposImportedVote.each(function(index) {
                const valor = $(this).val();
                
                if (valor === 'imported_vote') {
                    
                    const $questionContainer = $(this).closest('.vs-pergunta');
                    
                    window.VSAdmin.ImportedAnswers.setCurrentQuestion($questionContainer);
                    window.VSAdmin.ImportedAnswers.updateTable();
                    
                }
            });
            
        },

        importSingleQuestion: function(event) {
            const votingId = $(event.target).data('votacao-id');
            const questionIndex = $(event.target).data('question-index');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vs_obter_perguntas_votacao',
                    votacao_id: votingId,
                    question_index: questionIndex,
                    nonce: vs_admin.nonce
                },
                success: (response) => {
                    if (response.success && response.data && response.data[questionIndex]) {
                        const questionData = response.data[questionIndex];
                        const unifiedAnswers = questionData.respostas_unificadas || [];
                        
                        const importedAnswers = unifiedAnswers.map(answer => ({
                            value: answer.value || '',
                            value_unificada: answer.value_unificada || '',
                            qtd_votos: parseInt(answer.qtd_votos || 0)
                        }));

                        const answersJson = JSON.stringify({
                            questions: [{
                                question_source: questionData.label || '',
                                question_index: questionIndex + 1,
                                imported_answers: importedAnswers
                            }]
                        });
                        
                        // Atualizar campo oculto
                        this.getCurrentQuestion().find('.vs-imported-answers')
                            .val(answersJson)
                            .attr('vote-id-list', votingId);

                        // Atualizar tabela existente
                        this.updateTable();

                        // Fechar modal após importação
                        this.getCurrentQuestion().find('.vs-modal').hide();
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Erro ao importar pergunta:', error);
                }
            });
        },

        // Função consolidada do question-import.js para importação múltipla
        initQuestionImport: function(event) {
            const $modal = $(event.target).closest('.vs-modal');
            const $selectedQuestions = $modal.find('.vs-select-question:checked');
            
            if ($selectedQuestions.length === 0) {
                alert('Por favor, selecione pelo menos uma pergunta para importar.');
                return;
            }

            const votingId = $selectedQuestions.first().data('votacao-id');
            const selectedQuestionIndexes = [];
            
            $selectedQuestions.each(function() {
                selectedQuestionIndexes.push($(this).data('question-index'));
            });

            // Fazer chamada AJAX para obter dados das perguntas selecionadas
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vs_obter_perguntas_votacao',
                    votacao_id: votingId,
                    nonce: vs_admin.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        const allImportedAnswers = [];
                        
                        // Processar cada pergunta selecionada
                        selectedQuestionIndexes.forEach(questionIndex => {
                            if (response.data[questionIndex]) {
                                const questionData = response.data[questionIndex];
                                const unifiedAnswers = questionData.respostas_unificadas || [];
                                
                                const importedAnswers = unifiedAnswers.map(answer => ({
                                    value: answer.value || '',
                                    value_unificada: answer.value_unificada || '',
                                    qtd_votos: parseInt(answer.qtd_votos || 0)
                                }));

                                allImportedAnswers.push({
                                    vote_id: votingId,
                                    vote_title: questionData.vote_title || '',
                                    event_id: questionData.event_id || null,
                                    event_title: questionData.event_title || 'Evento sem nome',
                                    event_slug: questionData.event_slug || null,
                                    question_source: questionData.label || '',
                                    question_index: questionIndex + 1,
                                    imported_answers: importedAnswers
                                });
                            }
                        });

                        // JSON unificado com todas as respostas
                        const answersJson = JSON.stringify({
                            questions: allImportedAnswers,
                            selected_questions: {
                                [votingId]: selectedQuestionIndexes
                            }
                        });
                        
                        // Atualizar campo oculto
                        window.VSAdmin.ImportedAnswers.getCurrentQuestion().find('.vs-imported-answers')
                            .val(answersJson)
                            .attr('vote-id-list', votingId);

                        // Atualizar tabela existente
                        window.VSAdmin.ImportedAnswers.updateTable();

                        // Fechar modal após importação
                        $modal.hide();
                    }
                },
                error: function() {
                    alert('Erro ao importar perguntas. Tente novamente.');
                }
            });
        },

        // === HELPERS BÁSICOS PARA MANIPULAÇÃO DE JSON ===
        
        /**
         * Obtém dados JSON atuais do campo oculto .vs-imported-answers
         * Garante que sempre retorne uma estrutura válida mesmo com JSON malformado
         * @returns {Object} Dados parseados ou estrutura vazia padrão
         */
        getCurrentJsonData: function() {
            const $field = this.currentQuestion ? this.currentQuestion.find('.vs-imported-answers') : $('.vs-imported-answers');
            
            if (!$field.length) {
                console.warn('Campo .vs-imported-answers não encontrado');
                return this._getEmptyStructure();
            }
            
            try {
                const jsonStr = $field.val() || '{}';
                const data = JSON.parse(jsonStr);
                
                // Garantir estrutura válida com todos os campos obrigatórios
                return this._validateAndNormalizeStructure(data);
                
            } catch (e) {
                console.warn('JSON malformado no campo vs-imported-answers:', e.message);
                console.warn('Valor encontrado:', $field.val());
                return this._getEmptyStructure();
            }
        },

        /**
         * Define dados JSON no campo oculto .vs-imported-answers
         * Mantém a integridade da estrutura antes de salvar
         * @param {Object} data - Dados a serem salvos
         */
        setCurrentJsonData: function(data) {
            const $field = this.currentQuestion ? this.currentQuestion.find('.vs-imported-answers') : $('.vs-imported-answers');
            
            if (!$field.length) {
                console.error('Campo .vs-imported-answers não encontrado para salvar dados');
                return false;
            }
            
            try {
                // Validar e normalizar estrutura antes de salvar
                const normalizedData = this._validateAndNormalizeStructure(data);
                const jsonStr = JSON.stringify(normalizedData);
                
                $field.val(jsonStr);
                
                // Log para debug (apenas se modo debug estiver ativo)
                if (window.VS_IMPORT_MERGE_STRATEGY && window.VS_IMPORT_MERGE_STRATEGY.debugMode) {
                    console.log('Dados JSON salvos:', normalizedData);
                }
                
                return true;
                
            } catch (e) {
                console.error('Erro ao serializar dados JSON:', e.message);
                console.error('Dados que causaram erro:', data);
                return false;
            }
        },

        /**
         * Retorna estrutura vazia padrão
         * @private
         * @returns {Object} Estrutura vazia válida
         */
        _getEmptyStructure: function() {
            return {
                manual_items: [],
                imported_items: [],
                questions: [],
                selected_questions: []
            };
        },

        /**
         * Valida e normaliza estrutura de dados
         * Garante que todos os campos obrigatórios existam como arrays
         * @private
         * @param {Object} data - Dados a serem validados
         * @returns {Object} Estrutura normalizada
         */
        _validateAndNormalizeStructure: function(data) {
            if (!data || typeof data !== 'object') {
                return this._getEmptyStructure();
            }
            
            return {
                manual_items: Array.isArray(data.manual_items) ? data.manual_items : [],
                imported_items: Array.isArray(data.imported_items) ? data.imported_items : [],
                questions: Array.isArray(data.questions) ? data.questions : [],
                selected_questions: Array.isArray(data.selected_questions) ? data.selected_questions : []
            };
        },

        // === UTILITÁRIOS DE NORMALIZAÇÃO ===
        
        /**
         * Normaliza um valor de resposta garantindo estrutura consistente
         * Remove espaços extras, converte para string e aplica validações básicas
         * @param {*} value - Valor a ser normalizado
         * @returns {string} Valor normalizado
         */
        normalizeValue: function(value) {
            // Tratar valores nulos, undefined ou vazios
            if (value === null || value === undefined) {
                return '';
            }
            
            // Converter para string e remover espaços extras
            let normalized = String(value).trim();
            
            // Remover múltiplos espaços em branco consecutivos
            normalized = normalized.replace(/\s+/g, ' ');
            
            // Remover caracteres de controle (exceto quebras de linha)
            normalized = normalized.replace(/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/g, '');
            
            return normalized;
        },

        /**
         * Agrupa itens por vote_id para facilitar operações de merge
         * Cria um mapa organizado por ID de votação
         * @param {Array} items - Array de itens com propriedade vote_id
         * @returns {Object} Objeto agrupado por vote_id
         */
        groupByVoteId: function(items) {
            if (!Array.isArray(items)) {
                console.warn('groupByVoteId: esperado array, recebido:', typeof items);
                return {};
            }
            
            const grouped = {};
            
            items.forEach((item, index) => {
                // Validar se o item tem a estrutura mínima esperada
                if (!item || typeof item !== 'object') {
                    console.warn(`groupByVoteId: item inválido no índice ${index}:`, item);
                    return;
                }
                
                // Garantir que vote_id existe
                const voteId = item.vote_id || item.voteId || null;
                if (!voteId) {
                    console.warn(`groupByVoteId: vote_id ausente no item ${index}:`, item);
                    return;
                }
                
                // Normalizar vote_id para string
                const normalizedVoteId = String(voteId);
                
                // Inicializar array se não existir
                if (!grouped[normalizedVoteId]) {
                    grouped[normalizedVoteId] = [];
                }
                
                // Adicionar item ao grupo, garantindo que vote_id esteja normalizado
                const normalizedItem = {
                    ...item,
                    vote_id: normalizedVoteId
                };
                
                grouped[normalizedVoteId].push(normalizedItem);
            });
            
            return grouped;
        },

        /**
         * Insere ou atualiza perguntas na estrutura de dados
         * Implementa lógica de upsert baseada em question_id
         * @param {Array} existingQuestions - Array de perguntas existentes
         * @param {Array} newQuestions - Array de novas perguntas
         * @returns {Array} Array de perguntas atualizado
         */
        upsertQuestions: function(existingQuestions, newQuestions) {
            if (!Array.isArray(existingQuestions)) {
                console.warn('upsertQuestions: existingQuestions deve ser array, recebido:', typeof existingQuestions);
                existingQuestions = [];
            }
            
            if (!Array.isArray(newQuestions)) {
                console.warn('upsertQuestions: newQuestions deve ser array, recebido:', typeof newQuestions);
                return existingQuestions;
            }
            
            // Criar cópia do array existente para não modificar o original
            const result = [...existingQuestions];
            
            // Criar mapa de perguntas existentes por question_id para busca rápida
            const existingMap = new Map();
            result.forEach((question, index) => {
                const questionId = question.question_id || question.id;
                if (questionId) {
                    existingMap.set(String(questionId), index);
                }
            });
            
            // Processar cada nova pergunta
            newQuestions.forEach(newQuestion => {
                if (!newQuestion || typeof newQuestion !== 'object') {
                    console.warn('upsertQuestions: pergunta inválida ignorada:', newQuestion);
                    return;
                }
                
                const questionId = newQuestion.question_id || newQuestion.id;
                if (!questionId) {
                    console.warn('upsertQuestions: pergunta sem ID ignorada:', newQuestion);
                    return;
                }
                
                const normalizedQuestionId = String(questionId);
                
                // Normalizar pergunta
                const normalizedQuestion = {
                    ...newQuestion,
                    question_id: normalizedQuestionId,
                    question_text: this.normalizeValue(newQuestion.question_text || newQuestion.text || ''),
                    answers: Array.isArray(newQuestion.answers) ? newQuestion.answers.map(answer => {
                        if (typeof answer === 'string') {
                            return this.normalizeValue(answer);
                        }
                        if (answer && typeof answer === 'object') {
                            return {
                                ...answer,
                                text: this.normalizeValue(answer.text || answer.answer_text || ''),
                                value: this.normalizeValue(answer.value || answer.text || '')
                            };
                        }
                        return this.normalizeValue(answer);
                    }) : []
                };
                
                // Verificar se pergunta já existe
                if (existingMap.has(normalizedQuestionId)) {
                    // Atualizar pergunta existente
                    const existingIndex = existingMap.get(normalizedQuestionId);
                    result[existingIndex] = normalizedQuestion;
                } else {
                    // Adicionar nova pergunta
                    result.push(normalizedQuestion);
                    existingMap.set(normalizedQuestionId, result.length - 1);
                }
            });
            
            return result;
        },

        // === DETECÇÃO DE VALORES ATIVOS NO DOM ===
        
        /**
         * Obtém valores ativos importados do DOM para um voteId específico
         * Percorre elementos .vs-option-item para extrair realValue e visualValue
         * Aplica deduplicação baseada em realValue para evitar duplicatas
         * @param {string|number} voteId - ID da votação para filtrar valores
         * @returns {Array} Array de objetos com realValue, visualValue e metadata
         */
        getActiveImportedValues: function(voteId) {
            if (!voteId) {
                console.warn('getActiveImportedValues: voteId é obrigatório');
                return [];
            }
            
            const normalizedVoteId = String(voteId);
            const activeValues = [];
            const seenRealValues = new Set(); // Para deduplicação
            
            // Percorrer todos os elementos .vs-option-item no DOM
            $('.vs-option-item').each(function(index) {
                const $optionItem = $(this);
                
                try {
                    // Extrair realValue do campo oculto .vs-valor-real
                    const $realValueField = $optionItem.find('.vs-valor-real, input[name*="valores_reais"]');
                    const realValue = $realValueField.length ? $realValueField.val() : null;
                    
                    // Extrair visualValue do campo de texto visível
                    const $textField = $optionItem.find('input[type="text"][name*="options"]');
                    const visualValue = $textField.length ? $textField.val() : null;
                    
                    // Validar se os valores essenciais existem
                    if (!realValue || !visualValue) {
                        console.debug(`getActiveImportedValues: valores ausentes no item ${index}:`, {
                            realValue: realValue,
                            visualValue: visualValue,
                            element: $optionItem[0]
                        });
                        return true; // continue
                    }
                    
                    // Normalizar valores
                    const normalizedRealValue = this.normalizeValue(realValue);
                    const normalizedVisualValue = this.normalizeValue(visualValue);
                    
                    // Verificar se é uma opção importada (tem classe imported_question)
                    const isImported = $optionItem.hasClass('imported_question');
                    
                    // Extrair informações de contexto
                    const questionContainer = $optionItem.closest('.vs-pergunta, .vs-question-container');
                    const questionIndex = this._extractQuestionIndex(questionContainer);
                    const questionId = this._extractQuestionId(questionContainer);
                    
                    // Filtrar por voteId se especificado
                    // Nota: Como o voteId não está diretamente no DOM dos elementos de opção,
                    // vamos incluir todos os valores e deixar a filtragem para o contexto de uso
                    
                    // Aplicar deduplicação baseada em realValue
                    if (seenRealValues.has(normalizedRealValue)) {
                        console.debug(`getActiveImportedValues: valor real duplicado ignorado: "${normalizedRealValue}"`);
                        return true; // continue
                    }
                    
                    // Adicionar à lista de valores vistos
                    seenRealValues.add(normalizedRealValue);
                    
                    // Criar objeto de valor ativo
                    const activeValue = {
                        realValue: normalizedRealValue,
                        visualValue: normalizedVisualValue,
                        isImported: isImported,
                        questionIndex: questionIndex,
                        questionId: questionId,
                        domElement: $optionItem[0],
                        metadata: {
                            originalRealValue: realValue,
                            originalVisualValue: visualValue,
                            elementIndex: index,
                            hasRealValueField: $realValueField.length > 0,
                            hasTextField: $textField.length > 0
                        }
                    };
                    
                    activeValues.push(activeValue);
                    
                } catch (error) {
                    console.error(`getActiveImportedValues: erro ao processar item ${index}:`, error);
                    console.error('Elemento que causou erro:', $optionItem[0]);
                }
            }.bind(this));
            
            // Log para debug
            if (window.VS_IMPORT_MERGE_STRATEGY && window.VS_IMPORT_MERGE_STRATEGY.debugMode) {
                console.log(`getActiveImportedValues: encontrados ${activeValues.length} valores ativos:`, activeValues);
            }
            
            return activeValues;
        },

        /**
         * Extrai índice da pergunta do container DOM
         * @private
         * @param {jQuery} $container - Container da pergunta
         * @returns {number|null} Índice da pergunta ou null se não encontrado
         */
        _extractQuestionIndex: function($container) {
            if (!$container.length) return null;
            
            // Tentar extrair do atributo data-question-index
            let questionIndex = $container.data('question-index');
            if (questionIndex !== undefined) {
                return parseInt(questionIndex, 10);
            }
            
            // Tentar extrair de campos name que contêm vs_questions[index]
            const $nameField = $container.find('input[name*="vs_questions["], select[name*="vs_questions["], textarea[name*="vs_questions["]').first();
            if ($nameField.length) {
                const nameAttr = $nameField.attr('name');
                const match = nameAttr.match(/vs_questions\[(\d+)\]/);
                if (match) {
                    return parseInt(match[1], 10);
                }
            }
            
            // Fallback: usar posição relativa no DOM
            const $allQuestions = $('.vs-pergunta, .vs-question-container');
            const position = $allQuestions.index($container);
            return position >= 0 ? position : null;
        },

        /**
         * Extrai ID da pergunta do container DOM
         * @private
         * @param {jQuery} $container - Container da pergunta
         * @returns {string|null} ID da pergunta ou null se não encontrado
         */
        _extractQuestionId: function($container) {
            if (!$container.length) return null;
            
            // Tentar extrair do atributo data-question-id
            let questionId = $container.data('question-id');
            if (questionId !== undefined) {
                return String(questionId);
            }
            
            // Tentar extrair de campo oculto question_id
            const $idField = $container.find('input[name*="question_id"], input[name*="[id]"]').first();
            if ($idField.length) {
                const idValue = $idField.val();
                if (idValue) {
                    return String(idValue);
                }
            }
            
            // Fallback: usar questionIndex como ID
            const questionIndex = this._extractQuestionIndex($container);
            return questionIndex !== null ? `question_${questionIndex}` : null;
        },

        /**
         * Filtra valores ativos por voteId específico
         * Função auxiliar para filtrar resultados de getActiveImportedValues
         * @param {Array} activeValues - Array de valores ativos
         * @param {string|number} voteId - ID da votação para filtrar
         * @returns {Array} Array filtrado de valores ativos
         */
        filterActiveValuesByVoteId: function(activeValues, voteId) {
            if (!Array.isArray(activeValues)) {
                console.warn('filterActiveValuesByVoteId: activeValues deve ser um array');
                return [];
            }
            
            if (!voteId) {
                console.warn('filterActiveValuesByVoteId: voteId é obrigatório');
                return activeValues;
            }
            
            const normalizedVoteId = String(voteId);
            
            // Como o voteId não está diretamente nos elementos DOM,
            // esta função pode ser estendida para usar outros critérios de filtragem
            // Por enquanto, retorna todos os valores (a filtragem será feita em nível superior)
            
            return activeValues.filter(value => {
                // Implementar lógica de filtragem específica se necessário
                // Por exemplo, baseado em questionId, metadata, etc.
                return true; // Por enquanto, incluir todos
            });
        },

        /**
         * Determina se uma votação está "em uso" pelo usuário
         * Função central que verifica interseção entre valores ativos no DOM e valores armazenados
         * @param {string|number} voteId - ID da votação para verificar
         * @returns {boolean} true se a votação estiver em uso, false caso contrário
         */
        isVotingInUse: function(voteId) {
            if (!voteId) {
                console.warn('isVotingInUse: voteId é obrigatório');
                return false;
            }

            const normalizedVoteId = String(voteId);
            
            try {
                // 1. Obter valores ativos do DOM através de getActiveImportedValues
                const activeValues = this.getActiveImportedValues(voteId);
                
                if (!activeValues || activeValues.length === 0) {
                    console.debug(`isVotingInUse: nenhum valor ativo encontrado no DOM para voteId ${normalizedVoteId}`);
                    return false;
                }

                // 2. Extrair valores correspondentes do JSON armazenado
                const storedData = this.getCurrentJsonData();
                
                if (!storedData || !storedData.questions || storedData.questions.length === 0) {
                    console.debug(`isVotingInUse: nenhum dado armazenado encontrado para voteId ${normalizedVoteId}`);
                    return false;
                }

                // 3. Filtrar questões do voteId específico
                const voteQuestions = storedData.questions.filter(q => 
                    String(q.vote_id) === normalizedVoteId
                );

                if (voteQuestions.length === 0) {
                    console.debug(`isVotingInUse: nenhuma questão armazenada para voteId ${normalizedVoteId}`);
                    return false;
                }

                // 4. Criar conjunto de valores armazenados (realValue normalizado)
                const storedRealValues = new Set();
                voteQuestions.forEach(question => {
                    if (question.imported_answers && Array.isArray(question.imported_answers)) {
                        question.imported_answers.forEach(answer => {
                            const realValue = answer.value || answer.value_unificada;
                            if (realValue) {
                                const normalizedValue = this.normalizeValue(realValue);
                                storedRealValues.add(normalizedValue);
                            }
                        });
                    }
                });

                // 5. Criar conjunto de valores ativos do DOM (realValue normalizado)
                const activeRealValues = new Set();
                activeValues.forEach(activeValue => {
                    if (activeValue.realValue) {
                        activeRealValues.add(activeValue.realValue); // já normalizado por getActiveImportedValues
                    }
                });

                // 6. Verificar se existe interseção entre os dois conjuntos
                const hasIntersection = this._hasSetIntersection(storedRealValues, activeRealValues);

                // 7. Log para debug
                if (window.VS_IMPORT_MERGE_STRATEGY && window.VS_IMPORT_MERGE_STRATEGY.debugMode) {
                    console.log(`isVotingInUse: análise para voteId ${normalizedVoteId}:`, {
                        activeValuesCount: activeValues.length,
                        storedValuesCount: storedRealValues.size,
                        activeRealValues: Array.from(activeRealValues),
                        storedRealValues: Array.from(storedRealValues),
                        hasIntersection: hasIntersection,
                        result: hasIntersection
                    });
                }

                return hasIntersection;

            } catch (error) {
                console.error(`isVotingInUse: erro ao verificar uso da votação ${normalizedVoteId}:`, error);
                return false; // Em caso de erro, assumir que não está em uso (comportamento seguro)
            }
        },

        /**
         * Verifica se dois conjuntos têm interseção (elementos em comum)
         * @private
         * @param {Set} setA - Primeiro conjunto
         * @param {Set} setB - Segundo conjunto
         * @returns {boolean} true se houver interseção, false caso contrário
         */
        _hasSetIntersection: function(setA, setB) {
            if (!setA || !setB || setA.size === 0 || setB.size === 0) {
                return false;
            }

            // Iterar pelo conjunto menor para otimizar performance
            const [smallerSet, largerSet] = setA.size <= setB.size ? [setA, setB] : [setB, setA];
            
            for (const value of smallerSet) {
                if (largerSet.has(value)) {
                    return true; // Encontrou interseção
                }
            }
            
            return false; // Nenhuma interseção encontrada
        },

        /**
         * Combina dados existentes com novos dados baseado na estratégia escolhida
         * @param {Object} currentData - Dados atuais do JSON
         * @param {Object} newData - Novos dados a serem mesclados
         * @param {string} strategy - Estratégia de merge: 'PUSH' ou 'SUBSTITUIÇÃO'
         * @returns {Object} Dados mesclados
         */
        mergeVotingData: function(currentData, newData, strategy) {
            try {
                // Validar parâmetros de entrada
                if (!currentData || typeof currentData !== 'object') {
                    currentData = this._getEmptyStructure();
                }
                if (!newData || typeof newData !== 'object') {
                    console.warn('mergeVotingData: newData inválido');
                    return currentData;
                }
                if (!strategy || (strategy !== 'PUSH' && strategy !== 'SUBSTITUIÇÃO')) {
                    console.warn('mergeVotingData: estratégia inválida, usando PUSH como padrão');
                    strategy = 'PUSH';
                }

                // Garantir estrutura válida nos dados atuais
                currentData = this._validateAndNormalizeStructure(currentData);
                
                // Extrair vote_id dos novos dados
                const newVoteId = this._extractVoteIdFromNewData(newData);
                if (!newVoteId) {
                    console.warn('mergeVotingData: não foi possível extrair vote_id dos novos dados');
                    return currentData;
                }

                // Criar cópia profunda dos dados atuais para não modificar o original
                const mergedData = JSON.parse(JSON.stringify(currentData));

                if (strategy === 'PUSH') {
                    return this._mergePushStrategy(mergedData, newData, newVoteId);
                } else if (strategy === 'SUBSTITUIÇÃO') {
                    return this._mergeReplaceStrategy(mergedData, newData, newVoteId);
                }

                return mergedData;

            } catch (error) {
                console.error('Erro em mergeVotingData:', error);
                return currentData || this._getEmptyStructure();
            }
        },

        /**
         * Implementa estratégia PUSH: preserva dados existentes e adiciona apenas novos únicos
         * @param {Object} mergedData - Dados base para merge
         * @param {Object} newData - Novos dados
         * @param {string} newVoteId - ID da votação dos novos dados
         * @returns {Object} Dados mesclados
         */
        _mergePushStrategy: function(mergedData, newData, newVoteId) {
            // PUSH: Preservar tudo existente e adicionar apenas novos únicos
            
            // 1. Processar questions - adicionar apenas completamente novas
            if (newData.questions && Array.isArray(newData.questions)) {
                newData.questions.forEach(newQuestion => {
                    // Verificar se já existe uma pergunta com mesmo vote_id e question_index
                    const exists = mergedData.questions.some(existingQuestion => 
                        existingQuestion.vote_id === newQuestion.vote_id && 
                        existingQuestion.question_index === newQuestion.question_index
                    );
                    
                    if (!exists) {
                        mergedData.questions.push(newQuestion);
                    }
                });
            }

            // 2. Processar imported_items - adicionar apenas únicos
            if (newData.imported_items && Array.isArray(newData.imported_items)) {
                newData.imported_items.forEach(newItem => {
                    // Verificar duplicatas baseado em vote_id, question_index e value normalizado
                    const normalizedValue = this.normalizeValue(newItem.value || '');
                    const exists = mergedData.imported_items.some(existingItem => 
                        existingItem.vote_id === newItem.vote_id &&
                        existingItem.question_index === newItem.question_index &&
                        this.normalizeValue(existingItem.value || '') === normalizedValue
                    );
                    
                    if (!exists) {
                        mergedData.imported_items.push(newItem);
                    }
                });
            }

            // 3. Processar selected_questions - manter existentes e adicionar novos
            if (newData.selected_questions && typeof newData.selected_questions === 'object') {
                Object.keys(newData.selected_questions).forEach(voteId => {
                    if (!mergedData.selected_questions[voteId]) {
                        mergedData.selected_questions[voteId] = [];
                    }
                    
                    // Adicionar apenas índices que não existem
                    newData.selected_questions[voteId].forEach(questionIndex => {
                        if (!mergedData.selected_questions[voteId].includes(questionIndex)) {
                            mergedData.selected_questions[voteId].push(questionIndex);
                        }
                    });
                });
            }

            // 4. manual_items sempre preservados (nunca afetados por importações)
            // Não há ação necessária - já estão preservados na cópia

            return mergedData;
        },

        /**
         * Implementa estratégia SUBSTITUIÇÃO: remove dados do mesmo vote_id e adiciona todos os novos
         * @param {Object} mergedData - Dados base para merge
         * @param {Object} newData - Novos dados
         * @param {string} newVoteId - ID da votação dos novos dados
         * @returns {Object} Dados mesclados
         */
        _mergeReplaceStrategy: function(mergedData, newData, newVoteId) {
            // SUBSTITUIÇÃO: Remover dados do mesmo vote_id e adicionar todos os novos
            
            // 1. Remover questions existentes do mesmo vote_id
            mergedData.questions = mergedData.questions.filter(question => 
                question.vote_id !== newVoteId
            );
            
            // Adicionar todas as novas questions
            if (newData.questions && Array.isArray(newData.questions)) {
                mergedData.questions.push(...newData.questions);
            }

            // 2. Remover imported_items do mesmo vote_id
            mergedData.imported_items = mergedData.imported_items.filter(item => 
                item.vote_id !== newVoteId
            );
            
            // Adicionar todos os novos imported_items
            if (newData.imported_items && Array.isArray(newData.imported_items)) {
                mergedData.imported_items.push(...newData.imported_items);
            }

            // 3. Substituir selected_questions para este vote_id
            if (newData.selected_questions && typeof newData.selected_questions === 'object') {
                Object.keys(newData.selected_questions).forEach(voteId => {
                    mergedData.selected_questions[voteId] = [...newData.selected_questions[voteId]];
                });
            }

            // 4. manual_items sempre preservados (nunca afetados por importações)
            // Não há ação necessária - já estão preservados na cópia

            return mergedData;
        },

        /**
         * Extrai vote_id dos novos dados
         * @param {Object} newData - Novos dados
         * @returns {string|null} vote_id encontrado ou null
         */
        _extractVoteIdFromNewData: function(newData) {
            // Tentar extrair de questions
            if (newData.questions && Array.isArray(newData.questions) && newData.questions.length > 0) {
                return newData.questions[0].vote_id;
            }
            
            // Tentar extrair de imported_items
            if (newData.imported_items && Array.isArray(newData.imported_items) && newData.imported_items.length > 0) {
                return newData.imported_items[0].vote_id;
            }
            
            // Tentar extrair de selected_questions
            if (newData.selected_questions && typeof newData.selected_questions === 'object') {
                const voteIds = Object.keys(newData.selected_questions);
                if (voteIds.length > 0) {
                    return voteIds[0];
                }
            }
            
            return null;
        },

        /**
         * ORQUESTRADOR PRINCIPAL: Coordena todo o processo de decisão e merge
         * 
         * Fluxo:
         * 1. Obter dados existentes através de getCurrentJsonData
         * 2. Extrair todos os vote_ids únicos das novas perguntas
         * 3. Para cada vote_id, decidir a estratégia usando isVotingInUse
         * 4. Aplicar mergeVotingData com a estratégia apropriada
         * 5. Salvar o resultado final usando setCurrentJsonData
         * 6. Retornar os dados mesclados para uso posterior
         * 
         * @param {Array|Object} newQuestions - Array de questions ou objeto com estrutura completa
         * @returns {Object} Dados mesclados finais
         */
        /**
         * FUNÇÃO SEGURA COM FEATURE FLAG E FALLBACK
         * 
         * Esta é a função principal que deve ser usada externamente.
         * Ela verifica o feature flag e decide se usa a nova lógica ou fallback.
         * 
         * @param {Array|Object} newQuestions - Novos dados para importar
         * @returns {Object} Dados mesclados ou resultado do fallback
         */
        safeUpdateImportedAnswers: function(newQuestions) {
            const startTime = performance.now();
            const config = window.VS_IMPORT_MERGE_STRATEGY;
            
            // Log de entrada se debug ativo
            if (config.debugMode) {
                console.group('🔄 safeUpdateImportedAnswers iniciado');
                console.log('📥 Entrada:', newQuestions);
                console.log('⚙️ Configuração:', config.utils.getStatus());
            }
            
            try {
                // Verificar se nova lógica está habilitada
                if (!config.enabled) {
                    if (config.debugMode) {
                        console.warn('⚠️ Nova lógica desabilitada, usando fallback legacy');
                    }
                    return this._executeLegacyFallback(newQuestions, 'disabled');
                }
                
                // Validação de entrada se strict validation ativa
                if (config.advanced.strictValidation) {
                    const validationResult = this._validateInput(newQuestions);
                    if (!validationResult.isValid) {
                        if (config.debugMode) {
                            console.error('❌ Validação falhou:', validationResult.errors);
                        }
                        if (config.fallbackToLegacy) {
                            return this._executeLegacyFallback(newQuestions, 'validation_failed');
                        }
                        throw new Error('Validação de entrada falhou: ' + validationResult.errors.join(', '));
                    }
                }
                
                // Executar nova lógica com timeout se configurado
                let result;
                if (config.advanced.mergeTimeout > 0) {
                    result = this._executeWithTimeout(
                        () => this.updateImportedAnswers(newQuestions),
                        config.advanced.mergeTimeout
                    );
                } else {
                    result = this.updateImportedAnswers(newQuestions);
                }
                
                // Métricas de performance se habilitadas
                if (config.advanced.performanceMetrics) {
                    const duration = performance.now() - startTime;
                    console.log('📊 Performance - Nova lógica:', duration.toFixed(2) + 'ms');
                }
                
                if (config.debugMode) {
                    console.log('✅ Nova lógica executada com sucesso');
                    console.log('📤 Resultado:', result);
                    console.groupEnd();
                }
                
                return {
                    success: true,
                    message: 'Importação realizada com sucesso',
                    data: result
                };
                
            } catch (error) {
                if (config.debugMode) {
                    console.error('💥 Erro na nova lógica:', error);
                }
                
                // Decidir se usa fallback ou propaga erro
                if (config.fallbackToLegacy) {
                    if (config.debugMode) {
                        console.warn('🔄 Executando fallback para comportamento legacy');
                    }
                    
                    try {
                        const fallbackResult = this._executeLegacyFallback(newQuestions, 'error');
                        return {
                            success: true,
                            message: 'Importação realizada com sucesso (modo compatibilidade)',
                            data: fallbackResult
                        };
                    } catch (fallbackError) {
                        if (config.debugMode) {
                            console.error('💥 Erro também no fallback:', fallbackError);
                            console.groupEnd();
                        }
                        return {
                            success: false,
                            message: 'Falha tanto na nova lógica quanto no fallback: ' + error.message + ' | ' + fallbackError.message
                        };
                    }
                } else {
                    if (config.debugMode) {
                        console.groupEnd();
                    }
                    return {
                        success: false,
                        message: error.message || 'Erro desconhecido na importação'
                    };
                }
            }
        },
        
        /**
         * Executa comportamento legacy como fallback
         * @param {Array|Object} newQuestions - Dados para importar
         * @param {string} reason - Motivo do fallback
         * @returns {Object} Resultado do comportamento legacy
         */
        _executeLegacyFallback: function(newQuestions, reason) {
            const config = window.VS_IMPORT_MERGE_STRATEGY;
            
            if (config.debugMode) {
                console.group('🔙 Executando fallback legacy');
                console.log('📝 Motivo:', reason);
            }
            
            try {
                // Implementar comportamento legacy simplificado
                // Por enquanto, vamos usar uma versão simplificada que apenas adiciona os dados
                const currentData = this.getCurrentJsonData();
                
                // Estratégia legacy: sempre adicionar (equivalente a PUSH)
                let questionsToAdd = [];
                if (Array.isArray(newQuestions)) {
                    questionsToAdd = newQuestions;
                } else if (newQuestions && newQuestions.questions) {
                    questionsToAdd = newQuestions.questions;
                }
                
                // Adicionar questions sem verificação de duplicatas (comportamento legacy)
                if (questionsToAdd.length > 0) {
                    currentData.questions = currentData.questions || [];
                    currentData.questions.push(...questionsToAdd);
                    
                    // Adicionar imported_items básicos
                    questionsToAdd.forEach((question, questionIndex) => {
                        if (question.imported_answers) {
                            question.imported_answers.forEach((answer, answerIndex) => {
                                currentData.imported_items.push({
                                    real_value: answer.value || answer.real_value || '',
                                    visual_value: answer.display || answer.visual_value || answer.value || '',
                                    vote_id: question.vote_id,
                                    question_index: questionIndex,
                                    answer_index: answerIndex
                                });
                            });
                        }
                    });
                }
                
                this.setCurrentJsonData(currentData);
                
                if (config.debugMode) {
                    console.log('✅ Fallback legacy concluído');
                    console.log('📤 Resultado:', currentData);
                    console.groupEnd();
                }
                
                return {
                    success: true,
                    message: 'Importação realizada com sucesso (modo legacy)',
                    data: currentData
                };
                
            } catch (error) {
                if (config.debugMode) {
                    console.error('💥 Erro no fallback legacy:', error);
                    console.groupEnd();
                }
                return {
                    success: false,
                    message: 'Erro no fallback legacy: ' + error.message
                };
            }
        },
        
        /**
         * Valida entrada de dados
         * @param {*} input - Dados para validar
         * @returns {Object} Resultado da validação
         */
        _validateInput: function(input) {
            const errors = [];
            
            if (!input) {
                errors.push('Entrada é null ou undefined');
                return { isValid: false, errors };
            }
            
            if (Array.isArray(input)) {
                // Validar array de questions
                if (input.length === 0) {
                    errors.push('Array de questions está vazio');
                }
                
                input.forEach((question, index) => {
                    if (!question.vote_id) {
                        errors.push(`Question ${index} não possui vote_id`);
                    }
                });
            } else if (typeof input === 'object') {
                // Validar objeto com estrutura
                if (!input.questions && !input.imported_items) {
                    errors.push('Objeto não possui questions nem imported_items');
                }
            } else {
                errors.push('Entrada deve ser array ou objeto');
            }
            
            return {
                isValid: errors.length === 0,
                errors
            };
        },
        
        /**
         * Executa função com timeout
         * @param {Function} fn - Função para executar
         * @param {number} timeoutMs - Timeout em milissegundos
         * @returns {*} Resultado da função
         */
        _executeWithTimeout: function(fn, timeoutMs) {
            return new Promise((resolve, reject) => {
                const timer = setTimeout(() => {
                    reject(new Error(`Operação excedeu timeout de ${timeoutMs}ms`));
                }, timeoutMs);
                
                try {
                    const result = fn();
                    clearTimeout(timer);
                    resolve(result);
                } catch (error) {
                    clearTimeout(timer);
                    reject(error);
                }
            });
        },

        updateImportedAnswers: function(newQuestions) {
            try {
                // 1. Obter dados existentes
                const existingData = this.getCurrentJsonData();
                
                // 2. Normalizar entrada - aceitar tanto array de questions quanto objeto completo
                let questionsToProcess = [];
                let newDataStructure = {
                    questions: [],
                    imported_items: [],
                    selected_questions: {},
                    manual_items: [] // Sempre vazio para novos dados
                };
                
                if (Array.isArray(newQuestions)) {
                    // Entrada é array de questions
                    questionsToProcess = newQuestions;
                    newDataStructure.questions = newQuestions;
                    
                    // Gerar imported_items e selected_questions a partir das questions
                    newQuestions.forEach((question, questionIndex) => {
                        if (question.imported_answers && Array.isArray(question.imported_answers)) {
                            question.imported_answers.forEach((answer, answerIndex) => {
                                newDataStructure.imported_items.push({
                                    real_value: this.normalizeValue(answer.value || answer.real_value),
                                    visual_value: this.normalizeValue(answer.display || answer.visual_value || answer.value),
                                    vote_id: question.vote_id,
                                    question_index: questionIndex,
                                    answer_index: answerIndex
                                });
                            });
                            
                            // Adicionar aos selected_questions
                            if (!newDataStructure.selected_questions[question.vote_id]) {
                                newDataStructure.selected_questions[question.vote_id] = [];
                            }
                            newDataStructure.selected_questions[question.vote_id].push(questionIndex);
                        }
                    });
                } else if (newQuestions && typeof newQuestions === 'object') {
                    // Entrada é objeto com estrutura completa
                    newDataStructure = {
                        questions: newQuestions.questions || [],
                        imported_items: newQuestions.imported_items || [],
                        selected_questions: newQuestions.selected_questions || {},
                        manual_items: [] // Sempre vazio para novos dados
                    };
                    questionsToProcess = newDataStructure.questions;
                } else {
                    console.warn('updateImportedAnswers: entrada inválida', newQuestions);
                    return existingData;
                }
                
                // 3. Extrair todos os vote_ids únicos
                const voteIds = new Set();
                
                // De questions
                questionsToProcess.forEach(question => {
                    if (question.vote_id) {
                        voteIds.add(question.vote_id);
                    }
                });
                
                // De imported_items
                newDataStructure.imported_items.forEach(item => {
                    if (item.vote_id) {
                        voteIds.add(item.vote_id);
                    }
                });
                
                // De selected_questions
                Object.keys(newDataStructure.selected_questions).forEach(voteId => {
                    voteIds.add(voteId);
                });
                
                if (voteIds.size === 0) {
                    console.warn('updateImportedAnswers: nenhum vote_id encontrado nos novos dados');
                    return existingData;
                }
                
                // 4. Processar cada vote_id individualmente
                let mergedData = existingData;
                
                Array.from(voteIds).forEach(voteId => {
                    // 4.1. Decidir estratégia usando isVotingInUse
                    const isInUse = this.isVotingInUse(voteId);
                    const strategy = isInUse ? 'PUSH' : 'SUBSTITUIÇÃO';
                    
                    // Log para debug (se habilitado)
                    if (window.VS_IMPORT_MERGE_STRATEGY && window.VS_IMPORT_MERGE_STRATEGY.debugMode) {
                        console.log(`Vote ${voteId}: em uso = ${isInUse}, estratégia = ${strategy}`);
                    }
                    
                    // 4.2. Filtrar dados novos para este vote_id específico
                    const newDataForVoteId = {
                        questions: newDataStructure.questions.filter(q => q.vote_id === voteId),
                        imported_items: newDataStructure.imported_items.filter(item => item.vote_id === voteId),
                        selected_questions: {},
                        manual_items: [] // Sempre vazio
                    };
                    
                    // Adicionar selected_questions para este vote_id
                    if (newDataStructure.selected_questions[voteId]) {
                        newDataForVoteId.selected_questions[voteId] = newDataStructure.selected_questions[voteId];
                    }
                    
                    // 4.3. Aplicar mergeVotingData com a estratégia apropriada
                    mergedData = this.mergeVotingData(mergedData, newDataForVoteId, strategy);
                });
                
                // 5. Salvar o resultado final
                this.setCurrentJsonData(mergedData);
                
                // 6. Disparar evento para notificar outros componentes
                $(document).trigger('vs:imported-answers-updated', {
                    mergedData: mergedData,
                    processedVoteIds: Array.from(voteIds),
                    timestamp: new Date().toISOString()
                });
                
                // 7. Retornar os dados mesclados
                return mergedData;
                
            } catch (error) {
                console.error('Erro no orquestrador updateImportedAnswers:', error);
                
                // Em caso de erro, retornar dados existentes para evitar perda de dados
                return this.getCurrentJsonData();
            }
        }

    };

})(jQuery);

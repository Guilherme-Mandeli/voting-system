(function($) {
    'use strict';

    let currentQuestion = null;

    function updateImportedAnswersTable() {
    if (!currentQuestion || !currentQuestion.length) return;

    const jsonInput = currentQuestion.find('.vs-imported-answers');
    if (!jsonInput.length) return;

    const jsonData = jsonInput.val();
    const tbody = currentQuestion.find('.vs-imported-column tbody');

    if (!tbody.length) return;
    tbody.empty();

    try {
        const data = JSON.parse(jsonData);
        if (!data || typeof data !== 'object') throw new Error('JSON inválido');
        if (!data.perguntas || !Array.isArray(data.perguntas)) {
            tbody.append($('<tr>').append(
                $('<td>', { colspan: 5, style: 'text-align: center; padding: 20px;' })
                    .text('Nenhuma resposta foi importada.')
            ));
            return;
        }

        let rowNumber = 1;
        let hasAnswers = false;

        data.perguntas.forEach((pergunta) => {
            if (pergunta.imported_answers && pergunta.imported_answers.length > 0) {
                pergunta.imported_answers.forEach(resposta => {
                    const valorExibir = resposta.value_unificada || resposta.value;
                    if (!valorExibir) return;

                    hasAnswers = true;
                    const tr = $('<tr>');
                    tr.append(
                        $('<td>', { style: 'text-align: center; font-size: 12px;' }).text(rowNumber),
                        $('<td>').append($('<input>', {
                            type: 'checkbox',
                            class: 'vs-select-answer',
                            'data-valor': resposta.value,
                            'data-valor-unificado': resposta.value_unificada
                        })),
                        $('<td>').text(valorExibir),
                        $('<td>').text(resposta.qtd_votos || 0),
                        $('<td>').text(pergunta.question_source)
                    );
                    tbody.append(tr);
                    rowNumber++;
                });
            }
        });

        if (!hasAnswers) {
            tbody.append($('<tr>').append(
                $('<td>', { colspan: 5, style: 'text-align: center; padding: 20px;' })
                    .text('Nenhuma resposta foi importada.')
            ));
        }
    } catch (error) {
        console.error('Erro ao processar JSON:', error);
        tbody.append($('<tr>').append(
            $('<td>', { colspan: 5, style: 'text-align: center; padding: 20px;' })
                .text('Erro ao carregar respostas: ' + error.message)
        ));
    }
}

    $(document).ready(function() {
        initFieldTypeHandler();
        initImportedVoteModal();
    });

    function initFieldTypeHandler() {
        function createTableStructure(container) {
            // Criar estrutura de duas colunas
            const colunasContainer = $('<div>', {
                class: 'vs-columns-container',
                css: {
                    display: 'flex',
                    gap: '20px',
                    marginTop: '20px'
                }
            });

            // Coluna 1: Tabela de Respostas Importadas
            const colunaImportadas = $('<div>', { class: 'vs-imported-column', css: { flex: 1 } });
            const tabelaHeader = $('<div>', { class: 'vs-tabela-header' })
                .append($('<h4>').text('Respostas Importadas'))
                .append($('<button>', {
                    type: 'button',
                    class: 'button button-primary vs-add-selected',
                    text: 'Adicionar Selecionados'
                }));

            const tabela = $('<table>', { class: 'wp-list-table widefat fixed striped' })
                .append($('<thead>').append($('<tr>')
                    .append($('<th>', { style: 'width: 26px; text-align: center;' }).text('#'))
                    .append($('<th>', { class: 'check-column' })
                        .append($('<input>', { type: 'checkbox', class: 'vs-select-all-answers' })))
                    .append($('<th>').text('Resposta'))
                    .append($('<th>').text('Qtd. de Votos'))
                    .append($('<th>').text('Pergunta'))
                ));

            const tbody = $('<tbody>');
            const mensagemVazia = $('<tr>').append(
                $('<td>', { colspan: 5, style: 'text-align: center; padding: 20px;' })
                    .text('Nenhuma resposta foi importada.')
            );
            tbody.append(mensagemVazia);
            tabela.append(tbody);

            colunaImportadas.append(tabelaHeader, tabela);

            // Coluna 2: Opções Selecionadas
            const colunaOptions = $('<div>', { class: 'vs-options-column', css: { flex: 1 } });
            const optionsContainer = $('<div>', { class: 'vs-options' });
            
            colunaOptions.append(
                $('<label>').text('Opções:'),
                $('<br>'),
                optionsContainer,
                $('<button>', {
                    type: 'button',
                    class: 'button vs-add-option',
                    text: 'Adicionar Opção'
                })
            );

            // Montar estrutura final
            colunasContainer.append(colunaImportadas, colunaOptions);
            return colunasContainer;
        }

        $(document).on('change', '.vs-tipo-campo', function() {
            const container = $(this).closest('.vs-pergunta').find('.vs-votacao-anterior-container');
            const optionsContainer = $(this).closest('.vs-pergunta').find('.vs-options-container');

            if ($(this).val() === 'imported_vote') {
                container.show();
                optionsContainer.show();
                currentQuestion = $(this).closest('.vs-pergunta');

                // Remover estrutura antiga se existir
                currentQuestion.find('.vs-columns-container').remove();

                // Criar e adicionar nova estrutura
                const estruturaTabela = createTableStructure(container);
                container.append(estruturaTabela);

                // Atualizar tabela se já houver data
                updateImportedAnswersTable();
            } else {
                container.hide();
                if (!$(this).val().match(/^(select|radio|checkbox)$/)) {
                    optionsContainer.hide();
                }
            }
        });

        // Inicializar estado dos containers existentes
        $('.vs-tipo-campo').each(function() {
            if ($(this).val() === 'imported_vote') {
                const container = $(this).closest('.vs-pergunta').find('.vs-votacao-anterior-container');
                container.show();
                currentQuestion = $(this).closest('.vs-pergunta');

                // Criar e adicionar estrutura se não existir
                if (!currentQuestion.find('.vs-columns-container').length) {
                    const estruturaTabela = createTableStructure(container);
                    container.append(estruturaTabela);
                }

                updateImportedAnswersTable();
            }
        });
    }

    function initImportedVoteModal() {
        // Abrir modal
        $(document).on('click', '.vs-selecionar-votacao', function() {
            currentQuestion = $(this).closest('.vs-pergunta');
            const perguntaIndex = currentQuestion.find('[name*="[label]"]').attr('name').match(/\[(\d+)\]/)[1];
            const modal = $(`#vs-modal-votacao-anterior-${perguntaIndex}`);
            modal.show();
            loadVotings(modal);
        });

        // Fechar modal
        $(document).on('click', '.vs-modal-close, .vs-modal', function(e) {
            if (e.target === this) {
                $(this).closest('.vs-modal').hide();
                $('#vs-alerta-status').hide();
            }
        });

        // Filtros
        $(document).on('change', '.vs-filtro-ano, .vs-filtro-evento, .vs-filtro-status', function() {
            loadVotings($(this).closest('.vs-modal'));
        });

        $(document).on('input', '.vs-busca-votacao', debounce(function() {
            loadVotings($(this).closest('.vs-modal'));
        }, 300));

        // Prevenir submit do form ao pressionar Enter no campo de busca
        $(document).on('keypress', '.vs-busca-votacao', function(e) {
            if (e.which === 13) { // tecla 'Enter' -> 13
                e.preventDefault();
                loadVotings($(this).closest('.vs-modal'));
            }
        });

        // Botão de busca
        $(document).on('click', '.vs-buscar-votacao', function() {
            loadVotings($(this).closest('.vs-modal'));
        });

        function loadVotings(modal) {
            if (!modal) return;
            
            const filtros = {
                ano: modal.find('.vs-filtro-ano').val(),
                evento: modal.find('.vs-filtro-evento').val(),
                status: modal.find('.vs-filtro-status').val(),
                busca: modal.find('.vs-busca-votacao').val()
            };

            // Adiciona overlay de carregamento
            showLoadingOverlay(modal);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vs_buscar_votacoes',
                    filtros: filtros,
                    nonce: vs_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        renderVotings(response.data, modal.find('#vs-votings-list'));
                    }
                },
                error: function() {
                    modal.find('#vs-votings-list').append('<div class="vs-error">Erro ao carregar votações</div>');
                },
                complete: function() {
                    hideLoadingOverlay(modal);
                }
            });
        }

        function renderVotings(votacoes, container) {
            container.empty();
            container.append('<button type="button" class="button vs-back-to-list" style="display: none; margin-bottom: 15px;">← Voltar para lista de votações</button>');
            
            const listaVotacoes = $('<div class="vs-votings-list"></div>');
            container.append(listaVotacoes);

            // Criar tabela
            const tabela = $(`
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Ação</th>
                            <th>Nome</th>
                            <th>Código</th>
                            <th>Ano</th>
                            <th>Evento</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            `);
            
            // Adicionar linhas da tabela
            votacoes.forEach(votacao => {
                const linha = $('<tr>')
                    .append(`
                        <td>
                            <button type="button" class="button vs-load-questions" data-votacao-id="${votacao.id}">
                                Ver perguntas
                            </button>
                        </td>
                    `)
                    .append(`<td>${votacao.titulo}</td>`)
                    .append(`<td>${votacao.codigo || '-'}</td>`)
                    .append(`<td>${votacao.ano}</td>`)
                    .append(`<td>${votacao.evento || '-'}</td>`)
                    .append(`<td>${votacao.status}</td>`);
                
                tabela.find('tbody').append(linha);
            });
            
            listaVotacoes.append(tabela);
        }

        // Carregar perguntas da votação
        let isLoadingQuestions = false;

        // Função para mostrar overlay de carregamento
        function showLoadingOverlay(container) {
            // Remove qualquer overlay existente primeiro
            container.find('.vs-loading-overlay').remove();
            // Cria e anexa o novo overlay de carregamento
            const loadingOverlay = $('<div class="vs-loading-overlay"><div class="spinner"></div></div>');
            container.append(loadingOverlay);
            return loadingOverlay;
        }

        // Função para remover overlay de carregamento
        function hideLoadingOverlay(modal) {
            modal.find('.vs-loading-overlay').remove();
        }

        $(document).on('click', '.vs-load-questions', function() {
            if (isLoadingQuestions) return;
            isLoadingQuestions = true;
            
            const votacaoId = $(this).data('votacao-id');
            const modal = $(this).closest('.vs-modal');
            const listaVotacoes = modal.find('.vs-votings-list');
            const btnVoltar = modal.find('.vs-back-to-list');

            // Adiciona overlay de carregamento
            showLoadingOverlay(modal);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vs_obter_perguntas_votacao',
                    votacao_id: votacaoId,
                    nonce: vs_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Remove indicador de carregamento
                        showLoadingOverlay(modal).remove();
                        
                        // Reset da flag de carregamento
                        isLoadingQuestions = false;
                        
                        // Esconder lista de votações e mostrar botão voltar
                        listaVotacoes.hide();
                        btnVoltar.show();

                        // Criar container para perguntas
                        const perguntasContainer = $('<div class="vs-questions-list"></div>');
                        
                        // Adicionar texto explicativo e botão de importação
                        perguntasContainer.append(`
                            <div class="vs-perguntas-header">
                                <p class="vs-info-text">Ao importar, as respostas das perguntas selecionadas serão incorporadas como opções na nova pergunta, respeitando a unificação.</p>
                                <button type="button" class="button button-primary vs-import-selected" disabled>Importar Selecionados</button>
                            </div>
                        `);

                        // Criar tabela de perguntas
                        const tabela = $(`
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th class="check-column">
                                            <input type="checkbox" class="vs-select-all-questions">
                                        </th>
                                        <th>Pergunta</th>
                                        <th>Votos</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        `);

                        // Preencher tabela com perguntas
                        response.data.forEach((pergunta, index) => {                            
                            const linha = $(`
                                <tr>
                                    <td>
                                        <input type="checkbox" class="vs-select-question" 
                                            data-votacao-id="${votacaoId}" 
                                            data-question-index="${index}"
                                            data-status="${response.status}">
                                    </td>
                                    <td>#${index + 1} ${pergunta.label}</td>
                                    <td>${pergunta.total_votos || 0}</td>
                                </tr>
                            `);
                            
                            tabela.find('tbody').append(linha);
                        });

                        perguntasContainer.append(tabela);
                        modal.find('#vs-votings-list').append(perguntasContainer);

                        // Handler para checkbox "Selecionar todas"
                        modal.find('.vs-select-all-questions').on('change', function() {
                            const isChecked = $(this).prop('checked');
                            modal.find('.vs-select-question').prop('checked', isChecked);
                            updateImportButton(modal);
                        });

                        // Handler para checkboxes individuais
                        modal.find('.vs-select-question').on('change', function() {
                            updateImportButton(modal);
                        });
                    }
                }
            });
        });

        // Voltar para lista de votações
        $(document).on('click', '.vs-back-to-list', function() {
            const modal = $(this).closest('.vs-modal');
            const listaVotacoes = modal.find('.vs-votings-list');
            const perguntasLista = modal.find('.vs-questions-list');
            
            // Remover lista de perguntas
            perguntasLista.remove();
            // Esconder botão voltar e mostrar lista de votações
            $(this).hide();
            listaVotacoes.show();
        });

        // Botões do alerta
        $('#vs-alerta-status .vs-voltar').on('click', function() {
            $('#vs-alerta-status').hide();
        });

        $('#vs-alerta-status .vs-continuar').on('click', function() {
            const votacaoId = $(this).data('votacao-id');
            selectVoting(votacaoId);
            $('#vs-alerta-status').hide();
        });

        function selectVoting(votacaoId) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vs_obter_perguntas_votacao',
                    votacao_id: votacaoId,
                    nonce: vs_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showQuestionSelector(response.data, votacaoId);
                    }
                }
            });
        }

        function showQuestionSelector(questions, votacaoId) {
            const questionIndex = currentQuestion.find('[name*="[label]"]').attr('name').match(/\[(\d+)\]/)[1];
            const modal = $(`#vs-modal-votacao-anterior-${questionIndex}`);
            const lista = modal.find('.vs-modal-list');
            
            lista.empty();
            
            questions.forEach((question, index) => {
                const item = $('<div class="vs-pergunta-item">')
                    .append(`<h4>Pergunta ${index + 1}</h4>`)
                    .append(`<p>${question.label}</p>`)
                    .append(`<button type="button" class="button vs-importar-pergunta" 
                        data-votacao-id="${votacaoId}" 
                        data-question-index="${index}" 
                        data-pergunta-titulo="${question.label}">
                        Importar Respostas
                    </button>`);
                
                lista.append(item);
            });
        }

        // Remover seleção
        $(document).on('click', '.vs-remover-selecao', function() {
            const container = $(this).closest('.vs-pergunta');
            container.find('.vs-imported-answers')
                .val('')
                .attr('vote-id-list', '')
            container.find('.vs-votacao-selecionada').empty();
        });

        // Função para atualizar estado do botão de importar
        function updateImportButton(modal) {
            const temSelecionados = modal.find('.vs-select-question:checked').length > 0;
            modal.find('.vs-import-selected').prop('disabled', !temSelecionados);
        }

        // Handler para importar perguntas selecionadas
        $(document).on('click', '.vs-import-selected', function() {
            const modal = $(this).closest('.vs-modal');
            const perguntasSelecionadas = modal.find('.vs-select-question:checked');
            const votacaoId = perguntasSelecionadas.first().data('votacao-id');
            
            const questionIndex = currentQuestion.find('[name*="[label]"]').attr('name').match(/\[(\d+)\]/)[1];
            
            const questionsToImport = [];
            perguntasSelecionadas.each(function() {
                questionsToImport.push({
                    votacao_id: $(this).data('votacao-id'),
                    question_index: $(this).data('question-index'),
                    status: $(this).data('status')
                });
            });

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vs_obter_perguntas_votacao',
                    votacao_id: votacaoId,
                    nonce: vs_admin.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        const todasPerguntas = {
                            perguntas: []
                        };

                        questionsToImport.forEach(questionInfo => {
                            const questionData = response.data[questionInfo.question_index];
                            if (questionData) {
                                const respostasUnificadas = questionData.respostas_unificadas || [];
                                
                                const importedAnswers = respostasUnificadas.map(resposta => ({
                                    value: resposta.value || '',
                                    value_unificada: resposta.value_unificada || '',
                                    qtd_votos: parseInt(resposta.qtd_votos || 0)
                                }));

                                todasPerguntas.perguntas.push({
                                    vote_id: votacaoId,
                                    question_source: questionData.label || '',
                                    question_index: questionInfo.question_index + 1,
                                    imported_answers: importedAnswers
                                });
                            }
                        });

                        const respostasJson = JSON.stringify(todasPerguntas);
                
                        // Atualizar campo oculto com o JSON
                        currentQuestion.find('.vs-imported-answers')
                            .val(respostasJson)
                            .attr('vote-id-list', votacaoId);
                        
                        // Fechar o modal após importação
                        modal.hide();

                        // Criar e atualizar a estrutura de duas colunas
                        const container = currentQuestion.find('.vs-votacao-anterior-container');
                        // Criar elementos usando jQuery
                        const colunasContainer = $('<div>', {
                            class: 'vs-columns-container',
                            css: {
                                display: 'flex',
                                gap: '20px',
                                marginTop: '20px'
                            }
                        });

                        // Coluna 1: Tabela de Respostas Importadas
                        const colunaImportadas = $('<div>', { class: 'vs-imported-column', css: { flex: 1 } });
                        const tabelaHeader = $('<div>', { class: 'vs-tabela-header' })
                            .append($('<h4>').text('Respostas importadas'))
                            .append($('<button>', {
                                type: 'button',
                                class: 'button button-primary vs-add-selected',
                                text: 'Adicionar Selecionados'
                            }));

                        const tabela = $('<table>', { class: 'wp-list-table widefat fixed striped' })
                            .append($('<thead>').append($('<tr>')
                                .append($('<th>', { style: 'width: 26px; text-align: center;' }).text('#'))
                                .append($('<th>', { class: 'check-column' })
                                    .append($('<input>', { type: 'checkbox', class: 'vs-select-all-answers' })))
                                .append($('<th>').text('Resposta'))
                                .append($('<th>').text('Qtd. de Votos'))
                                .append($('<th>').text('Pergunta'))
                            ))
                            .append($('<tbody>'));

                        colunaImportadas.append(tabelaHeader, tabela);

                        // Coluna 2: Formulário de Adição
                        const colunaAdicao = $('<div>', { class: 'vs-coluna-adicao', css: { flex: 1 } });
                        const optionsContainer = $('<div>', { class: 'vs-options' });

                        const optionItem = $('<div>', { class: 'vs-option-item', css: { marginBottom: '5px' } })
                            .append($('<input>', {
                                type: 'text',
                                name: `vs_questions[${perguntaIndex}][options][]`,
                                css: { width: '90%' },
                                placeholder: 'Opção 1'
                            }))
                            .append($('<input>', {
                                type: 'hidden',
                                name: `vs_questions[${perguntaIndex}][valores_reais][]`,
                                class: 'vs-valor-real'
                            }))
                            .append($('<button>', {
                                type: 'button',
                                class: 'button button-small vs-remove-option',
                                text: 'Remover'
                            }));

                        const addButton = $('<button>', {
                            type: 'button',
                            class: 'button vs-add-option',
                            id: `vs-add-option-${perguntaIndex}`, // ID único baseado no índice da pergunta
                            text: 'Adicionar Opção',
                            'data-question-index': perguntaIndex // Adicionar o índice como data attribute
                        });

                        optionsContainer.append(optionItem, addButton);
                        colunaAdicao.append($('<label>').text('Opções:'), $('<br>'), optionsContainer);

                        // Montar a estrutura final
                        colunasContainer.append(colunaImportadas, colunaAdicao);

                        // Inserir as colunas após o botão "Abrir votações"
                        container.append(colunasContainer);

                        // Atualizar a tabela usando os data do input hidden
                        updateImportedAnswersTable();
                    } else {
                        console.error('Erro ao importar respostas:', response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro na requisição AJAX:', error);
                }
            });
        });

        // Função para importar uma pergunta específica
        function importQuestion(votacaoId, perguntaIndex) {
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'vs_obter_perguntas_votacao',
            votacao_id: votacaoId,
            question_index: perguntaIndex,
            nonce: vs_admin.nonce
        },
        success: function(response) {
            if (response.success && response.data && response.data[perguntaIndex]) {
                const perguntaData = response.data[perguntaIndex];
                const respostasUnificadas = perguntaData.respostas_unificadas || [];
                
                const respostasImportadas = respostasUnificadas.map(resposta => ({
                    value: resposta.value || '',
                    value_unificada: resposta.value_unificada || '',
                    qtd_votos: parseInt(resposta.qtd_votos || 0)
                }));

                const respostasJson = JSON.stringify({
                    perguntas: [{
                        question_source: perguntaData.label || '',
                        question_index: perguntaIndex + 1,
                        imported_answers: respostasImportadas
                    }]
                });
                
                // Atualizar campo oculto
                currentQuestion.find('.vs-imported-answers')
                    .val(respostasJson)
                    .attr('vote-id-list', votacaoId);

                // Atualizar tabela existente
                updateImportedAnswersTable();

                // Fechar modal após importação
                currentQuestion.find('.vs-modal').hide();
            }
        }
    });
}

        // Ordenação
        $(document).on('click', '.vs-ordenar-valor, .vs-ordenar-votos', function() {
            const isValor = $(this).hasClass('vs-ordenar-valor');
            const tbody = $(this).closest('.vs-imported-column').find('table tbody');
            const rows = tbody.find('tr').get();
            const ordem = $(this).data('ordem') || 'asc';

            rows.sort((a, b) => {
                const aVal = isValor ? 
                    $(a).find('td:eq(2)').text() : 
                    parseInt($(a).find('td:eq(3)').text());
                const bVal = isValor ? 
                    $(b).find('td:eq(2)').text() : 
                    parseInt($(b).find('td:eq(3)').text());

                return ordem === 'asc' ? 
                    (aVal > bVal ? 1 : -1) : 
                    (aVal < bVal ? 1 : -1);
            });

            $(this).data('ordem', ordem === 'asc' ? 'desc' : 'asc');
            $(this).text($(this).text().replace(/[▲▼]/, ordem === 'asc' ? '▼' : '▲'));

            tbody.empty().append(rows);
        });

        // Handler para adicionar opção selecionada
        $(document).on('click', '.vs-add-selected', function() {
            const container = $(this).closest('.vs-columns-container');
            const perguntaContainer = container.closest('.vs-pergunta');
            const perguntaIndex = perguntaContainer.find('[name*="[label]"]').attr('name').match(/\[(\d+)\]/)[1];
            const optionsContainer = container.find('.vs-options-column .vs-options');
            
            // Obter respostas selecionadas da tabela
            const respostasSelecionadas = container.find('.vs-select-answer:checked');
            
            respostasSelecionadas.each(function() {
                const tr = $(this).closest('tr');
                const valorReal = $(this).data('valor');
                const valorVisual = tr.find('td:eq(2)').text();
                const perguntaOrigem = tr.find('td:eq(4)').text();
                
                // Verificar se a opção já existe
                const existe = optionsContainer.find('.vs-valor-real').filter(function() {
                    return $(this).val() === valorReal;
                }).length > 0;
                
                if (!existe) {
                    // Estrutura HTML unificada para opções
                    const $optionItem = $('<div>', { 
                        class: 'vs-option-item', 
                        css: { marginBottom: '5px' } 
                    });
                    
                    const $textInput = $('<input>', {
                        type: 'text',
                        name: `vs_questions[${perguntaIndex}][options][]`,
                        value: valorVisual,
                        style: 'width: 90%;'
                    });
                    
                    const $hiddenInput = $('<input>', {
                        type: 'hidden',
                        name: `vs_questions[${perguntaIndex}][valores_reais][]`,
                        value: valorReal,
                        class: 'vs-valor-real'
                    });
                    
                    const $sourceSpan = $('<span>', {
                        class: 'vs-source-question',
                        style: 'color: #666; font-size: 0.9em; display: block; margin-top: 2px;'
                    }).text(perguntaOrigem);
                    
                    const $removeButton = $('<button>', {
                        type: 'button',
                        class: 'button button-small vs-remove-option',
                        text: 'Remover',
                        style: 'margin-left: 5px;'
                    });
                    
                    // Montar estrutura unificada
                    $optionItem.append($textInput, $hiddenInput, $sourceSpan, $removeButton);
                    
                    // Inserir antes do botão "Adicionar Opção"
                    optionsContainer.find('.vs-add-option').before($optionItem);
                }
            });
            
            // Desmarcar checkboxes após adicionar
            respostasSelecionadas.prop('checked', false);
        });

        // Handler para remover opção
        $(document).on('click', '.vs-remove-option', function() {
            $(this).closest('.vs-option-item').remove();
        });

    }

    // Filtro de perguntas fora do modal
    $('.vs-search-question').on('input', function() {
        const busca = $(this).val().toLowerCase();
        $('.vs-option-item').each(function() {
            const pergunta = $(this).find('.vs-source-question').text().toLowerCase();
            $(this).toggle(pergunta.includes(busca));
        });
    });

    // Handler para selecionar todas as respostas
    $(document).on('change', '.vs-select-all-answers', function() {
        const isChecked = $(this).prop('checked');
        $(this).closest('table').find('.vs-select-answer').prop('checked', isChecked);
    });

    // Adicionar as chamadas aqui, depois da definição da função
    $('.vs-tipo-campo').each(function() {
        if ($(this).val() === 'imported_vote') {
            currentQuestion = $(this).closest('.vs-pergunta');
            updateImportedAnswersTable();
        }
    });

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
})(jQuery);

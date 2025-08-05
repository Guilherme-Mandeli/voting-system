/**
 * Módulo para gestão do modal de importação de votações
 */
(function($) {
    'use strict';

    window.VSAdmin = window.VSAdmin || {};
    window.VSAdmin.VotingModal = {
        
        isLoading: false,
        isLoadingQuestions: false,
        currentPage: 1,
        totalPages: 1,

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Abrir modal
            $(document).on('click', '.vs-selecionar-votacao', this.openModal.bind(this));
            
            // Fechar modal
            $(document).on('click', '.vs-modal-close, .vs-modal', this.closeModal.bind(this));
            
            // Busca
            $(document).on('input', '.vs-busca-votacao', 
                window.VSAdmin.Utils.debounce(this.handleSearch.bind(this), 300)
            );
            
            // Paginação
            $(document).on('click', '.vs-pagination .button', this.handlePagination.bind(this));
            
            // Carregar perguntas
            $(document).on('click', '.vs-load-questions', this.loadQuestions.bind(this));
            
            // Voltar para lista
            $(document).on('click', '.vs-back-to-list', this.backToList.bind(this));
            
            // Importar selecionadas
            $(document).on('click', '.vs-import-selected', this.importSelected.bind(this));

            // Botões do alerta de status
            $('#vs-alerta-status .vs-voltar').on('click', this.hideStatusAlert.bind(this));
            $('#vs-alerta-status .vs-continuar').on('click', this.continueWithVoting.bind(this));

            // Filtros
            $(document).on('change', '.vs-filtro-ano, .vs-filtro-evento, .vs-filtro-status', this.handleFilter.bind(this));
            $(document).on('keypress', '.vs-busca-votacao', this.handleSearchKeypress.bind(this));
            $(document).on('click', '.vs-buscar-votacao', this.handleSearchButton.bind(this));
        },

        openModal: function(event) {
            const $questionContainer = $(event.target).closest('.vs-pergunta');
            
            // Definir currentQuestion no módulo ImportedAnswers
            if (window.VSAdmin.ImportedAnswers) {
                window.VSAdmin.ImportedAnswers.setCurrentQuestion($questionContainer);
            }
            
            const questionIndex = $questionContainer.find('[name*="[label]"]').attr('name').match(/\[(\d+)\]/)[1];
            const $modal = $(`#vs-modal-votacao-anterior-${questionIndex}`);
            $modal.show();
            this.loadVotings($modal);
        },

        closeModal: function(event) {
            if (event.target === this || $(event.target).hasClass('vs-modal-close')) {
                $(event.target).closest('.vs-modal').hide();
                $('#vs-alerta-status').hide();
            }
        },

        handleSearch: function(event) {
            this.currentPage = 1;
            this.loadVotings($(event.target).closest('.vs-modal'));
        },

        handleSearchKeypress: function(event) {
            if (event.which === 13) { // Enter key
                event.preventDefault();
                this.handleSearch(event);
            }
        },

        handleSearchButton: function(event) {
            this.handleSearch(event);
        },

        handleFilter: function(event) {
            this.currentPage = 1;
            this.loadVotings($(event.target).closest('.vs-modal'));
        },

        handlePagination: function(event) {
            const page = parseInt($(event.target).data('page'));
            if (page && page !== this.currentPage) {
                this.currentPage = page;
                this.loadVotings($(event.target).closest('.vs-modal'));
            }
        },

        loadVotings: function($modal) {
            if (this.isLoading) return;
            this.isLoading = true;

            const searchTerm = $modal.find('.vs-busca-votacao').val();
            const statusFilter = $modal.find('.vs-filtro-status').val();
            const dateFilter = $modal.find('.vs-filtro-ano').val();
            const eventFilter = $modal.find('.vs-filtro-evento').val();

            window.VSAdmin.Utils.showLoadingOverlay($modal);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vs_buscar_votacoes',
                    filtros: {
                        ano: dateFilter,
                        evento: eventFilter,
                        status: statusFilter,
                        busca: searchTerm
                    },
                    page: this.currentPage,
                    nonce: vs_admin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        window.VSAdmin.VotingModal.renderVotings(response.data, $modal.find('#vs-votings-list'));
                        this.totalPages = response.total_pages || 1;
                        this.renderPagination($modal, this.currentPage, this.totalPages);
                    } else {
                        $modal.find('#vs-votings-list').html('<p>Erro ao carregar votações.</p>');
                    }
                    this.isLoading = false;
                    window.VSAdmin.Utils.hideLoadingOverlay($modal);
                },
                error: () => {
                    $modal.find('#vs-votings-list').html('<p>Erro ao carregar votações.</p>');
                    this.isLoading = false;
                    window.VSAdmin.Utils.hideLoadingOverlay($modal);
                }
            });
        },

        renderVotings: function(votacoes, $container) {
            $container.empty();
            $container.append('<button type="button" class="button vs-back-to-list" style="display: none; margin-bottom: 15px;">← Voltar para lista de votações</button>');
            
            const $listaVotacoes = $('<div class="vs-votings-list"></div>');
            $container.append($listaVotacoes);

            // Criar tabela como no original
            const $tabela = $(`
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
                const $linha = $('<tr>')
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
                
                $tabela.find('tbody').append($linha);
            });
            
            $listaVotacoes.append($tabela);
        },

        renderPagination: function($modal, currentPage, totalPages) {
            if (totalPages <= 1) return;
            
            let paginationHtml = '<div class="vs-pagination">';
            for (let i = 1; i <= totalPages; i++) {
                const activeClass = i === currentPage ? 'button-primary' : 'button-secondary';
                paginationHtml += `<button type="button" class="button ${activeClass}" data-page="${i}">${i}</button>`;
            }
            paginationHtml += '</div>';
            $modal.find('.vs-pagination-container').html(paginationHtml);
        },

        loadQuestions: function(event) {
            if (this.isLoadingQuestions) return;
            this.isLoadingQuestions = true;
            
            const votingId = $(event.target).data('votacao-id');
            const status = $(event.target).data('status');
            const $modal = $(event.target).closest('.vs-modal');
            const $votingsList = $modal.find('.vs-votings-list');
            const $backButton = $modal.find('.vs-back-to-list');

            // Verificar se a votação está inativa e mostrar alerta
            if (status === 'inactive') {
                this.showStatusAlert(votingId);
                this.isLoadingQuestions = false;
                return;
            }

            this.selectVoting(votingId, $modal);

            // Adiciona overlay de carregamento
            window.VSAdmin.Utils.showLoadingOverlay($modal);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vs_obter_perguntas_votacao',
                    votacao_id: votingId,
                    nonce: vs_admin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Remove indicador de carregamento
                        window.VSAdmin.Utils.hideLoadingOverlay($modal);
                        
                        // Reset da flag de carregamento
                        this.isLoadingQuestions = false;
                        
                        // Esconder lista de votações e mostrar botão voltar
                        $votingsList.hide();
                        $backButton.show();

                        this.renderQuestions(response.data, $modal, votingId, response.status);
                    }
                },
                error: () => {
                    this.isLoadingQuestions = false;
                    window.VSAdmin.Utils.hideLoadingOverlay($modal);
                }
            });
        },

        renderQuestions: function(questions, $modal, votingId, status) {
            // Criar container para perguntas
            const $questionsContainer = $('<div class="vs-questions-list"></div>');
            
            // Adicionar texto explicativo e botão de importação
            $questionsContainer.append(`
                <div class="vs-perguntas-header">
                    <p class="vs-info-text">Ao importar, as respostas das perguntas selecionadas serão incorporadas como opções na nova pergunta, respeitando a unificação.</p>
                    <button type="button" class="button button-primary vs-import-selected" disabled>Importar Selecionadas</button>
                </div>
            `);

            // Criar tabela de perguntas
            const $table = $(`
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
            questions.forEach((question, index) => {                            
                const $row = $(`
                    <tr>
                        <td>
                            <input type="checkbox" class="vs-select-question" 
                                data-votacao-id="${votingId}" 
                                data-question-index="${index}"
                                data-status="${status}">
                        </td>
                        <td>#${index + 1} ${question.label}</td>
                        <td>${question.total_votos || 0}</td>
                    </tr>
                `);
                
                $table.find('tbody').append($row);
            });

            $questionsContainer.append($table);
            $modal.find('#vs-votings-list').append($questionsContainer);

            // Handler para checkbox "Selecionar todas"
            $modal.find('.vs-select-all-questions').on('change', function() {
                const isChecked = $(this).prop('checked');
                $modal.find('.vs-select-question').prop('checked', isChecked);
                window.VSAdmin.VotingModal.updateImportButton($modal);
            });

            // Handler para checkboxes individuais
            $modal.find('.vs-select-question').on('change', function() {
                window.VSAdmin.VotingModal.updateImportButton($modal);
            });
        },

        backToList: function(event) {
            const $modal = $(event.target).closest('.vs-modal');
            const $votingsList = $modal.find('.vs-votings-list');
            const $questionsList = $modal.find('.vs-questions-list');
            
            // Remover lista de perguntas
            $questionsList.remove();
            // Esconder botão voltar e mostrar lista de votações
            $(event.target).hide();
            $votingsList.show();
        },

        updateImportButton: function($modal) {
            const hasSelected = $modal.find('.vs-select-question:checked').length > 0;
            $modal.find('.vs-import-selected').prop('disabled', !hasSelected);
        },

        importSelected: function(event) {
            const $modal = $(event.target).closest('.vs-modal');
            const $selectedQuestions = $modal.find('.vs-select-question:checked');
            
            if ($selectedQuestions.length === 0) {
                alert('Selecione pelo menos uma pergunta para importar.');
                return;
            }

            const votingId = $selectedQuestions.first().data('votacao-id');
            
            // Obter currentQuestion do módulo ImportedAnswers
            const currentQuestion = window.VSAdmin.ImportedAnswers ? 
                window.VSAdmin.ImportedAnswers.getCurrentQuestion() : null;
                
            if (!currentQuestion || !currentQuestion.length) {
                console.error('currentQuestion não definida');
                return;
            }
            
            const questionIndex = currentQuestion.find('[name*="[label]"]').attr('name').match(/\[(\d+)\]/)[1];
            
            const questionsToImport = [];
            $selectedQuestions.each(function() {
                questionsToImport.push({
                    votacao_id: $(this).data('votacao-id'),
                    question_index: $(this).data('question-index'),
                    status: $(this).data('status')
                });
            });

            this.processImport(questionsToImport, votingId, $modal);
        },

        processImport: function(questionsToImport, votingId, $modal) {
            if (!questionsToImport || questionsToImport.length === 0) {
                alert('Selecione pelo menos uma pergunta para importar.');
                return;
            }

            window.VSAdmin.Utils.showLoadingOverlay($modal);

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
                        const allQuestions = {
                            perguntas: []
                        };

                        questionsToImport.forEach(questionInfo => {
                            const questionData = response.data[questionInfo.question_index];
                            if (questionData) {
                                const unifiedAnswers = questionData.respostas_unificadas || [];
                                
                                const importedAnswers = unifiedAnswers.map(answer => ({
                                    value: answer.value || '',
                                    value_unificada: answer.value_unificada || '',
                                    qtd_votos: parseInt(answer.qtd_votos || 0)
                                }));

                                allQuestions.perguntas.push({
                                    vote_id: votingId,
                                    question_source: questionData.label || '',
                                    question_index: questionInfo.question_index + 1,
                                    imported_answers: importedAnswers
                                });
                            }
                        });

                        const answersJson = JSON.stringify(allQuestions);
                
                        // Obter currentQuestion do módulo ImportedAnswers
                        const currentQuestion = window.VSAdmin.ImportedAnswers ? 
                            window.VSAdmin.ImportedAnswers.getCurrentQuestion() : null;
                            
                        if (currentQuestion && currentQuestion.length) {
                            // Atualizar campo oculto com o JSON
                            currentQuestion.find('.vs-imported-answers')
                                .val(answersJson)
                                .attr('vote-id-list', votingId);
                            
                            // Fechar o modal após importação
                            $modal.hide();

                            // Criar e atualizar a estrutura de duas colunas
                            window.VSAdmin.VotingModal.createImportStructure();

                            // Atualizar a tabela usando os dados do input hidden
                            if (window.VSAdmin.ImportedAnswers) {
                                window.VSAdmin.ImportedAnswers.updateTable();
                            }
                        }
                    } else {
                        console.error('Erro ao importar respostas:', response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro na requisição AJAX:', error);
                },
                complete: function() {
                    window.VSAdmin.Utils.hideLoadingOverlay($modal);
                }
            });
        },

        createImportStructure: function() {
            // Obter currentQuestion do módulo ImportedAnswers
            const currentQuestion = window.VSAdmin.ImportedAnswers ? 
                window.VSAdmin.ImportedAnswers.getCurrentQuestion() : null;
                
            if (!currentQuestion || !currentQuestion.length) return;

            const questionIndex = currentQuestion.find('[name*="[label]"]').attr('name').match(/\[(\d+)\]/)[1];
            const container = currentQuestion.find('.vs-votacao-anterior-container');
            
            if (container.find('.vs-columns-container').length > 0) return;

            // Criar container de duas colunas (estrutura unificada)
            const $columnsContainer = $('<div>', { 
                class: 'vs-columns-container', 
                css: { 
                    display: 'flex', 
                    gap: '20px',
                    marginTop: '20px'
                } 
            });

            // Coluna 1: Tabela de Respostas Importadas (estrutura unificada)
            const $importedColumn = $('<div>', { class: 'vs-imported-column', css: { flex: 1 } });
            const $tableHeader = $('<div>', { class: 'vs-tabela-header' })
                .append($('<h4>').text('Respostas importadas'))
                .append($('<button>', {
                    type: 'button',
                    class: 'button button-primary vs-add-selected',
                    text: 'Adicionar Selecionados'
                }));

            const $table = $('<table>', { class: 'wp-list-table widefat fixed striped' })
                .append($('<thead>').append($('<tr>')
                    .append($('<th>', { style: 'width: 26px; text-align: center;' }).text('#'))
                    .append($('<th>', { class: 'check-column' })
                        .append($('<input>', { type: 'checkbox', class: 'vs-select-all-answers' })))
                    .append($('<th>').text('Resposta'))
                    .append($('<th>').text('Qtd. de Votos'))
                    .append($('<th>').text('Pergunta'))
                ))
                .append($('<tbody>'));

            // Adicionar mensagem vazia inicial (estrutura unificada)
            const $tbody = $table.find('tbody');
            const $mensagemVazia = $('<tr>').append(
                $('<td>', { colspan: 5, style: 'text-align: center; padding: 20px;' })
                    .text('Nenhuma resposta foi importada.')
            );
            $tbody.append($mensagemVazia);

            $importedColumn.append($tableHeader, $table);

            // Coluna 2: Opções Selecionadas (estrutura unificada)
            const $optionsColumn = $('<div>', { class: 'vs-options-column', css: { flex: 1 } });
            const $optionsContainer = $('<div>', { class: 'vs-options' });
            
            $optionsColumn.append(
                $('<label>').text('Opções:'),
                $('<br>'),
                $optionsContainer,
                $('<button>', {
                    type: 'button',
                    class: 'button vs-add-option',
                    text: 'Adicionar Opção'
                })
            );

            // Montar a estrutura final (estrutura unificada)
            $columnsContainer.append($importedColumn, $optionsColumn);

            // Inserir as colunas no container
            container.append($columnsContainer);
        },

        selectVoting: function(votingId, $modal) {
            if (!$modal) {
                // Obter currentQuestion do módulo ImportedAnswers
                const currentQuestion = window.VSAdmin.ImportedAnswers ? 
                    window.VSAdmin.ImportedAnswers.getCurrentQuestion() : null;
                    
                if (!currentQuestion || !currentQuestion.length) return;
                
                const questionIndex = currentQuestion.find('[name*="[label]"]').attr('name').match(/\[(\d+)\]/)[1];
                $modal = $(`#vs-modal-votacao-anterior-${questionIndex}`);
            }

            const $votingsList = $modal.find('.vs-votings-list');
            const $backButton = $modal.find('.vs-back-to-list');

            window.VSAdmin.Utils.showLoadingOverlay($modal);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vs_obter_perguntas_votacao',
                    votacao_id: votingId,
                    nonce: vs_admin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        window.VSAdmin.Utils.hideLoadingOverlay($modal);
                        this.isLoadingQuestions = false;
                        
                        // Esconder lista de votações e mostrar botão voltar
                        $votingsList.hide();
                        $backButton.show();

                        this.renderQuestions(response.data, $modal, votingId, response.status);
                    }
                },
                error: () => {
                    this.isLoadingQuestions = false;
                    window.VSAdmin.Utils.hideLoadingOverlay($modal);
                }
            });
        },

        showStatusAlert: function(votingId) {
            const $alert = $('#vs-alerta-status');
            $alert.find('.vs-continuar').data('votacao-id', votingId);
            $alert.show();
        },

        hideStatusAlert: function() {
            $('#vs-alerta-status').hide();
        },

        continueWithVoting: function(event) {
            const votingId = $(event.target).data('votacao-id');
            this.selectVoting(votingId);
            this.hideStatusAlert();
        },

        showQuestionSelector: function() {
            // Implementação para mostrar seletor de perguntas
            console.log('Showing question selector');
        }
    };

})(jQuery);

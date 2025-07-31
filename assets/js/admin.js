(function($) {
    'use strict';

    let currentPergunta = null;

    function atualizarTabelaRespostasImportadas() {
        console.log('Iniciando atualizarTabelaRespostasImportadas');
        
        const jsonData = currentPergunta.find('.vs-respostas-importadas').val();
        console.log('JSON Data encontrado:', jsonData);
        
        if (!jsonData) {
            console.log('JSON Data está vazio, retornando...');
            return;
        }

        try {
            const dados = JSON.parse(jsonData);
            console.log('JSON parseado com sucesso:', dados);
            
            if (!dados.perguntas || !Array.isArray(dados.perguntas)) {
                console.error('Formato de dados inválido:', dados);
                return;
            }
            
            const tbody = currentPergunta.find('.vs-coluna-importadas tbody');
            if (!tbody.length) {
                console.error('Tbody não encontrado');
                return;
            }
            
            tbody.empty();
            console.log('Tbody limpo');
            
            let numeroLinha = 1;
            dados.perguntas.forEach((pergunta, indexPergunta) => {
                console.log(`Processando pergunta ${indexPergunta + 1}:`, pergunta);
                
                if (pergunta.respostas_importadas && pergunta.respostas_importadas.length > 0) {
                    pergunta.respostas_importadas.forEach(resposta => {
                        const valorExibir = resposta.value_unificada || resposta.value;
                        if (!valorExibir) {
                            console.log('Resposta sem valor, pulando...');
                            return;
                        }
                        
                        console.log('Criando linha para resposta:', valorExibir);
                        
                        const tr = $('<tr>');
                        tr.append(`
                            <td style="text-align: center; font-size: 12px;">${numeroLinha}</td>
                            <td><input type="checkbox" class="vs-selecionar-resposta" data-valor="${resposta.value}" data-valor-unificado="${resposta.value_unificada}"></td>
                            <td>${valorExibir}</td>
                            <td>${resposta.qtd_votos}</td>
                            <td>${pergunta.pergunta_origem}</td>
                        `);
                        tbody.append(tr);
                        console.log(`Linha ${numeroLinha} adicionada ao tbody`);
                        numeroLinha++;
                    });
                } else {
                    console.log(`Pergunta ${indexPergunta + 1} não tem respostas importadas`);
                }
            });
        } catch (error) {
            console.error('Erro ao processar JSON:', error);
        }
    }

    $(document).ready(function() {
        initTipoCampoHandler();
        initVotacaoAnteriorModal();
    });

    function initTipoCampoHandler() {
        $(document).on('change', '.vs-tipo-campo', function() {
            const container = $(this).closest('.vs-pergunta').find('.vs-votacao-anterior-container');
            if ($(this).val() === 'votacao_anterior') {
                container.show();
                currentPergunta = $(this).closest('.vs-pergunta');
                atualizarTabelaRespostasImportadas();
            } else {
                container.hide();
            }
        });

        // Inicializar estado dos containers
        $('.vs-tipo-campo').each(function() {
            if ($(this).val() === 'votacao_anterior') {
                $(this).closest('.vs-pergunta').find('.vs-votacao-anterior-container').show();
                currentPergunta = $(this).closest('.vs-pergunta');
                atualizarTabelaRespostasImportadas();
            }
        });
    }

    function initVotacaoAnteriorModal() {
        // Abrir modal
        $(document).on('click', '.vs-selecionar-votacao', function() {
            currentPergunta = $(this).closest('.vs-pergunta');
            const perguntaIndex = currentPergunta.find('[name*="[label]"]').attr('name').match(/\[(\d+)\]/)[1];
            const modal = $(`#vs-modal-votacao-anterior-${perguntaIndex}`);
            modal.show();
            carregarVotacoes(modal);
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
            carregarVotacoes($(this).closest('.vs-modal'));
        });

        $(document).on('input', '.vs-busca-votacao', debounce(function() {
            carregarVotacoes($(this).closest('.vs-modal'));
        }, 300));

        // Prevenir submit do form ao pressionar Enter no campo de busca
        $(document).on('keypress', '.vs-busca-votacao', function(e) {
            if (e.which === 13) { // tecla 'Enter' -> 13
                e.preventDefault();
                carregarVotacoes($(this).closest('.vs-modal'));
            }
        });

        // Botão de busca
        $(document).on('click', '.vs-buscar-votacao', function() {
            carregarVotacoes($(this).closest('.vs-modal'));
        });

        function carregarVotacoes(modal) {
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
                        renderizarVotacoes(response.data, modal.find('#vs-lista-votacoes'));
                    }
                },
                error: function() {
                    modal.find('#vs-lista-votacoes').append('<div class="vs-error">Erro ao carregar votações</div>');
                },
                complete: function() {
                    hideLoadingOverlay(modal);
                }
            });
        }

        function renderizarVotacoes(votacoes, container) {
            container.empty();
            container.append('<button type="button" class="button vs-voltar-lista" style="display: none; margin-bottom: 15px;">← Voltar para lista de votações</button>');
            
            const listaVotacoes = $('<div class="vs-lista-votacoes"></div>');
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
                            <button type="button" class="button vs-carregar-perguntas" data-votacao-id="${votacao.id}">
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
        let isLoadingPerguntas = false;

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

        $(document).on('click', '.vs-carregar-perguntas', function() {
            if (isLoadingPerguntas) return;
            isLoadingPerguntas = true;
            
            const votacaoId = $(this).data('votacao-id');
            const modal = $(this).closest('.vs-modal');
            const listaVotacoes = modal.find('.vs-lista-votacoes');
            const btnVoltar = modal.find('.vs-voltar-lista');

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
                        isLoadingPerguntas = false;
                        
                        // Esconder lista de votações e mostrar botão voltar
                        listaVotacoes.hide();
                        btnVoltar.show();

                        // Criar container para perguntas
                        const perguntasContainer = $('<div class="vs-perguntas-lista"></div>');
                        
                        // Adicionar texto explicativo e botão de importação
                        perguntasContainer.append(`
                            <div class="vs-perguntas-header">
                                <p class="vs-info-text">Ao importar, as respostas das perguntas selecionadas serão incorporadas como opções na nova pergunta, respeitando a unificação.</p>
                                <button type="button" class="button button-primary vs-importar-selecionadas" disabled>Importar Selecionados</button>
                            </div>
                        `);

                        // Criar tabela de perguntas
                        const tabela = $(`
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th class="check-column">
                                            <input type="checkbox" class="vs-selecionar-todas-perguntas">
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
                                        <input type="checkbox" class="vs-selecionar-pergunta" 
                                            data-votacao-id="${votacaoId}" 
                                            data-pergunta-index="${index}"
                                            data-status="${response.status}">
                                    </td>
                                    <td>#${index + 1} ${pergunta.label}</td>
                                    <td>${pergunta.total_votos || 0}</td>
                                </tr>
                            `);
                            
                            tabela.find('tbody').append(linha);
                        });

                        perguntasContainer.append(tabela);
                        modal.find('#vs-lista-votacoes').append(perguntasContainer);

                        // Handler para checkbox "Selecionar todas"
                        modal.find('.vs-selecionar-todas-perguntas').on('change', function() {
                            const isChecked = $(this).prop('checked');
                            modal.find('.vs-selecionar-pergunta').prop('checked', isChecked);
                            atualizarBotaoImportar(modal);
                        });

                        // Handler para checkboxes individuais
                        modal.find('.vs-selecionar-pergunta').on('change', function() {
                            atualizarBotaoImportar(modal);
                        });
                    }
                }
            });
        });

        // Voltar para lista de votações
        $(document).on('click', '.vs-voltar-lista', function() {
            const modal = $(this).closest('.vs-modal');
            const listaVotacoes = modal.find('.vs-lista-votacoes');
            const perguntasLista = modal.find('.vs-perguntas-lista');
            
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
            selecionarVotacao(votacaoId);
            $('#vs-alerta-status').hide();
        });

        function selecionarVotacao(votacaoId) {
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
                        mostrarSeletorPerguntas(response.data, votacaoId);
                    }
                }
            });
        }

        function mostrarSeletorPerguntas(perguntas, votacaoId) {
            const perguntaIndex = currentPergunta.find('[name*="[label]"]').attr('name').match(/\[(\d+)\]/)[1];
            const modal = $(`#vs-modal-votacao-anterior-${perguntaIndex}`);
            const lista = modal.find('.vs-modal-list');
            
            lista.empty();
            
            perguntas.forEach((pergunta, index) => {
                const item = $('<div class="vs-pergunta-item">')
                    .append(`<h4>Pergunta ${index + 1}</h4>`)
                    .append(`<p>${pergunta.label}</p>`)
                    .append(`<button type="button" class="button vs-importar-pergunta" 
                        data-votacao-id="${votacaoId}" 
                        data-pergunta-index="${index}" 
                        data-pergunta-titulo="${pergunta.label}">
                        Importar Respostas
                    </button>`);
                
                lista.append(item);
            });
        }

        // Remover seleção
        $(document).on('click', '.vs-remover-selecao', function() {
            const container = $(this).closest('.vs-pergunta');
            container.find('.vs-votacao-anterior-id').val('');
            container.find('.vs-votacao-selecionada').empty();
        });

        // Função para atualizar estado do botão de importar
        function atualizarBotaoImportar(modal) {
            const temSelecionados = modal.find('.vs-selecionar-pergunta:checked').length > 0;
            modal.find('.vs-importar-selecionadas').prop('disabled', !temSelecionados);
        }

        // Handler para importar perguntas selecionadas
        $(document).on('click', '.vs-importar-selecionadas', function() {
            const modal = $(this).closest('.vs-modal');
            const perguntasSelecionadas = modal.find('.vs-selecionar-pergunta:checked');
            const votacaoId = perguntasSelecionadas.first().data('votacao-id');
            
            // Obter o índice da pergunta do currentPergunta
            const perguntaIndex = currentPergunta.find('[name*="[label]"]').attr('name').match(/\[(\d+)\]/)[1];
            
            // Array para armazenar todas as perguntas e índices selecionados
            const perguntasParaImportar = [];
            perguntasSelecionadas.each(function() {
                perguntasParaImportar.push({
                    votacao_id: $(this).data('votacao-id'),
                    pergunta_index: $(this).data('pergunta-index'),
                    status: $(this).data('status')
                });
            });

            // Fazer uma única requisição para obter todas as perguntas
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

                        // Processar apenas as perguntas selecionadas
                        perguntasParaImportar.forEach(perguntaInfo => {
                            const perguntaData = response.data[perguntaInfo.pergunta_index];
                            if (perguntaData) {
                                const respostasUnificadas = perguntaData.respostas_unificadas || [];
                                
                                const respostasImportadas = respostasUnificadas.map(resposta => ({
                                    value: resposta.value || '',
                                    value_unificada: resposta.value_unificada || '',
                                    qtd_votos: parseInt(resposta.qtd_votos || 0)
                                }));

                                todasPerguntas.perguntas.push({
                                    pergunta_origem: perguntaData.label || '',
                                    pergunta_index: perguntaInfo.pergunta_index + 1,
                                    respostas_importadas: respostasImportadas
                                });
                            }
                        });

                        const respostasJson = JSON.stringify(todasPerguntas);
                
                        // Atualizar campo oculto com o JSON
                        currentPergunta.find('.vs-respostas-importadas').val(respostasJson);
                        // Atualizar o campo votacao_anterior_id com o ID da votação
                        currentPergunta.find('.vs-votacao-anterior-id').val(votacaoId);
                        
                        // Fechar o modal após importação
                        modal.hide();

                        // Criar e atualizar a estrutura de duas colunas
                        const container = currentPergunta.find('.vs-votacao-anterior-container');
                        // Criar elementos usando jQuery
                        const colunasContainer = $('<div>', {
                            class: 'vs-colunas-container',
                            css: {
                                display: 'flex',
                                gap: '20px',
                                marginTop: '20px'
                            }
                        });

                        // Coluna 1: Tabela de Respostas Importadas
                        const colunaImportadas = $('<div>', { class: 'vs-coluna-importadas', css: { flex: 1 } });
                        const tabelaHeader = $('<div>', { class: 'vs-tabela-header' })
                            .append($('<h4>').text('Respostas importadas'))
                            .append($('<button>', {
                                type: 'button',
                                class: 'button button-primary vs-adicionar-selecionadas',
                                text: 'Adicionar Selecionados'
                            }));

                        const tabela = $('<table>', { class: 'wp-list-table widefat fixed striped' })
                            .append($('<thead>').append($('<tr>')
                                .append($('<th>', { style: 'width: 26px; text-align: center;' }).text('#'))
                                .append($('<th>', { class: 'check-column' })
                                    .append($('<input>', { type: 'checkbox', class: 'vs-selecionar-todas-respostas' })))
                                .append($('<th>').text('Resposta'))
                                .append($('<th>').text('Qtd. de Votos'))
                                .append($('<th>').text('Pergunta'))
                            ))
                            .append($('<tbody>'));

                        colunaImportadas.append(tabelaHeader, tabela);

                        // Coluna 2: Formulário de Adição
                        const colunaAdicao = $('<div>', { class: 'vs-coluna-adicao', css: { flex: 1 } });
                        const opcoesContainer = $('<div>', { class: 'vs-opcoes' });

                        const opcaoItem = $('<div>', { class: 'vs-opcao-item', css: { marginBottom: '5px' } })
                            .append($('<input>', {
                                type: 'text',
                                name: `vs_perguntas[${perguntaIndex}][opcoes][]`,
                                css: { width: '90%' },
                                placeholder: 'Opção 1'
                            }))
                            .append($('<input>', {
                                type: 'hidden',
                                name: `vs_perguntas[${perguntaIndex}][valores_reais][]`,
                                class: 'vs-valor-real'
                            }))
                            .append($('<button>', {
                                type: 'button',
                                class: 'button button-small vs-remove-opcao',
                                text: 'Remover'
                            }));

                        const addButton = $('<button>', {
                            type: 'button',
                            class: 'button vs-add-opcao',
                            id: `vs-add-opcao-${perguntaIndex}`, // ID único baseado no índice da pergunta
                            text: 'Adicionar Opção',
                            'data-pergunta-index': perguntaIndex // Adicionar o índice como data attribute
                        });

                        opcoesContainer.append(opcaoItem, addButton);
                        colunaAdicao.append($('<label>').text('Opções:'), $('<br>'), opcoesContainer);

                        // Montar a estrutura final
                        colunasContainer.append(colunaImportadas, colunaAdicao);

                        // Inserir as colunas após o botão "Abrir votações"
                        container.append(colunasContainer);

                        // Atualizar a tabela usando os dados do input hidden
                        atualizarTabelaRespostasImportadas();
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
        function importarPergunta(votacaoId, perguntaIndex) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vs_obter_perguntas_votacao',
                    votacao_id: votacaoId,
                    pergunta_index: perguntaIndex,
                    nonce: vs_admin.nonce
                },
                success: function(response) {
                    if (response.success && response.data && response.data[perguntaIndex]) {
                        const perguntaData = response.data[perguntaIndex];
                        console.log('Dados da pergunta recebidos:', perguntaData);
                        
                        const respostasUnificadas = perguntaData.respostas_unificadas || [];
                        console.log('Respostas unificadas:', respostasUnificadas);
                        
                        const respostasImportadas = respostasUnificadas.map(resposta => ({
                            value: resposta.value || '',
                            value_unificada: resposta.value_unificada || '',
                            qtd_votos: parseInt(resposta.qtd_votos || 0)
                        }));
                        console.log('Respostas formatadas:', respostasImportadas);

                        const respostasJson = JSON.stringify({
                            perguntas: [{
                                pergunta_origem: perguntaData.label || '',
                                pergunta_index: perguntaIndex + 1,
                                respostas_importadas: respostasImportadas
                            }]
                        });
                        
                        currentPergunta.find('.vs-respostas-importadas').val(respostasJson);
                        currentPergunta.find('.vs-votacao-anterior-id').val(votacaoId);
                        console.log('JSON atualizado no campo hidden:', respostasJson);
                        
                        atualizarTabelaRespostasImportadas();
                    } else {
                        console.error('Erro ao importar respostas:', response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro na requisição AJAX:', error);
                }
            });
        }

        // Importar respostas
        $(document).on('click', '.vs-importar-respostas', function() {
            const votacaoId = $(this).data('votacao-id');
            const perguntaIndex = $(this).data('pergunta-index');
            importarPergunta(votacaoId, perguntaIndex);
            $('#vs-modal-votacao-anterior').hide();
        });

        // Ordenação
        $(document).on('click', '.vs-ordenar-valor, .vs-ordenar-votos', function() {
            const isValor = $(this).hasClass('vs-ordenar-valor');
            const tbody = $(this).closest('.vs-coluna-selecao').find('.vs-tabela-opcoes tbody');
            const rows = tbody.find('tr').get();
            const ordem = $(this).data('ordem');

            rows.sort((a, b) => {
                const aVal = isValor ? 
                    $(a).find('td:eq(1)').text() : 
                    parseInt($(a).find('td:eq(2)').text());
                const bVal = isValor ? 
                    $(b).find('td:eq(1)').text() : 
                    parseInt($(b).find('td:eq(2)').text());

                return ordem === 'asc' ? 
                    (aVal > bVal ? 1 : -1) : 
                    (aVal < bVal ? 1 : -1);
            });

            $(this).data('ordem', ordem === 'asc' ? 'desc' : 'asc');
            $(this).text($(this).text().replace(/[▲▼]/, ordem === 'asc' ? '▼' : '▲'));

            tbody.empty().append(rows);
        });

        // Handler para adicionar opção selecionada
        $(document).on('click', '.vs-adicionar-selecionadas', function() {
            const container = $(this).closest('.vs-colunas-container');
            const opcoesContainer = container.find('.vs-coluna-adicao .vs-opcoes');
            const respostasSelecionadas = container.find('.vs-selecionar-resposta:checked');

            respostasSelecionadas.each(function() {
                const tr = $(this).closest('tr');
                const valorReal = $(this).val();
                const valorVisual = tr.find('td:eq(1)').text().replace(/^\d+\.\s*/, '');
                const perguntaOrigem = tr.find('td:eq(3)').text();

                // Verificar se a opção já existe
                const existe = opcoesContainer.find('.vs-valor-real').filter(function() {
                    return $(this).val() === valorReal;
                }).length > 0;

                if (!existe) {
                    const opcaoHtml = `
                        <div class="vs-opcao-item" style="margin-bottom: 5px;">
                            <input type="text" name="vs_perguntas[${perguntaIndex}][opcoes][]" 
                                value="${valorVisual}" style="width: 90%;">
                            <input type="hidden" name="vs_perguntas[${perguntaIndex}][valores_reais][]" 
                                value="${valorReal}" class="vs-valor-real">
                            <button type="button" class="button button-small vs-remove-opcao">Remover</button>
                            <span class="vs-pergunta-origem" style="color: #666; font-size: 0.9em; margin-left: 10px;">
                                ${perguntaOrigem}
                            </span>
                        </div>
                    `;
                    opcoesContainer.find('.vs-add-opcao').before(opcaoHtml);
                }
            });
        });

        // Handler para remover opção
        $(document).on('click', '.vs-remove-opcao', function() {
            $(this).closest('.vs-opcao-item').remove();
        });

    }

// Filtro de perguntas fora do modal
    $('.vs-busca-pergunta').on('input', function() {
        const busca = $(this).val().toLowerCase();
        $('.vs-opcao-item').each(function() {
            const pergunta = $(this).find('.vs-pergunta-origem').text().toLowerCase();
            $(this).toggle(pergunta.includes(busca));
        });
    });

    // Handler para selecionar todas as respostas
    $(document).on('change', '.vs-selecionar-todas-respostas', function() {
        const isChecked = $(this).prop('checked');
        $(this).closest('table').find('.vs-selecionar-resposta').prop('checked', isChecked);
    });

    // Adicionar as chamadas aqui, depois da definição da função
    $('.vs-tipo-campo').each(function() {
        if ($(this).val() === 'votacao_anterior') {
            currentPergunta = $(this).closest('.vs-pergunta');
            atualizarTabelaRespostasImportadas();
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

/**
 * Módulo para gerenciar tipos de campo de perguntas
 */
(function($) {
    'use strict';

    window.VSAdmin = window.VSAdmin || {};

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

    function initFieldTypeHandler() {
        // Event handler principal para mudança de tipo de campo
        $(document).on('change', '.vs-tipo-campo', function() {
            const $questionContainer = $(this).closest('.vs-pergunta');
            const container = $questionContainer.find('.vs-votacao-anterior-container');
            const optionsContainer = $questionContainer.find('.vs-options-container');

            if ($(this).val() === 'imported_vote') {
                container.show();
                optionsContainer.show();
                
                // Definir currentQuestion no módulo ImportedAnswers
                if (window.VSAdmin.ImportedAnswers) {
                    window.VSAdmin.ImportedAnswers.setCurrentQuestion($questionContainer);
                }

                // Remover estrutura antiga se existir
                $questionContainer.find('.vs-columns-container').remove();

                // Criar e adicionar nova estrutura
                const estruturaTabela = createTableStructure(container);
                container.append(estruturaTabela);

                // Atualizar tabela se já houver dados
                if (window.VSAdmin.ImportedAnswers) {
                    window.VSAdmin.ImportedAnswers.updateTable();
                }
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
                const $questionContainer = $(this).closest('.vs-pergunta');
                const container = $questionContainer.find('.vs-votacao-anterior-container');
                container.show();
                
                // Definir currentQuestion no módulo ImportedAnswers
                if (window.VSAdmin.ImportedAnswers) {
                    window.VSAdmin.ImportedAnswers.setCurrentQuestion($questionContainer);
                }

                // Criar e adicionar estrutura se não existir
                if (!$questionContainer.find('.vs-columns-container').length) {
                    const estruturaTabela = createTableStructure(container);
                    container.append(estruturaTabela);
                }

                // Atualizar tabela se já houver dados
                if (window.VSAdmin.ImportedAnswers) {
                    window.VSAdmin.ImportedAnswers.updateTable();
                }
            }
        });
    }

    // Exportar para namespace global
    window.VSAdmin = window.VSAdmin || {};
    window.VSAdmin.FieldTypeHandler = {
        init: initFieldTypeHandler
    };

})(jQuery);
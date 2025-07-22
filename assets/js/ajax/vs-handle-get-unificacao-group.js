/* global jQuery, vsUnificacaoModalData */

/**
 * Script de Unificação de Respostas
 *
 * Este script controla:
 *  - Seleção de linhas (respostas) na tabela principal.
 *  - Abertura de modal para Unificação.
 *  - Criação de nova Resposta Unificada (valor livre digitado).
 *  - Aplicação de uma Resposta Unificada existente (lista gerada da Coluna 2).
 *  - Requisição AJAX para salvar a(s) unificação(ões) no servidor.
 *  - Requisição AJAX "Ver todos" para listar todas as respostas associadas a uma unificação.
 *  - Exibição de notificações no estilo WordPress dentro do modal.
 *
 * Dependências:
 *  - jQuery
 *  - Objeto global "vsUnificacaoModalData" (injetado via wp_localize_script)
 *      {
 *          ajaxurl:    "https://.../wp-admin/admin-ajax.php",
 *          nonce:      "xxxxxxxxxx",  // criado com wp_create_nonce( 'vs_unificacao_nonce' )
 *          votacao_id: 123
 *      }
 *
 * Observações:
 *  - Este arquivo substitui os antigos "ajax-get-respostas-unificadas.js" e "unificacao-modal.js".
 *  - Se os arquivos antigos ainda estiverem enfileirados, remova-os para evitar conflitos.
 */
jQuery( document ).ready( function( $ ) {

    /* ---------------------------------------------------------------------
     * Referências aos elementos do modal (já existem no markup do view).
     * ------------------------------------------------------------------ */
    var $modal   = $( '#modal-unificacao' );
    var $overlay = $( '#modal-overlay' );

    /* ---------------------------------------------------------------------
     * Util: Objeto de config vindo do PHP (wp_localize_script).
     * ------------------------------------------------------------------ */
    var VSU = window.vsUnificacaoModalData || {};
    VSU.votacao_id = parseInt( VSU.votacao_id, 10 ) || 0;

    /* ---------------------------------------------------------------------
     * Fecha o modal
     * ------------------------------------------------------------------ */
    function closeModal() {
        $modal.animate(
            { opacity: 0, top: '40%' },
            125,
            function() {
                $modal.hide();
                $overlay.fadeOut( 125 );
            }
        );
    }

    /* ---------------------------------------------------------------------
     * Abre o modal com conteúdo HTML
     *
     * @param {string} title       Título exibido no topo do modal.
     * @param {string} contentHtml HTML do corpo do modal.
     * ------------------------------------------------------------------ */
    function openModal( title, contentHtml ) {
        var modalContent  = '<h2>' + title + '</h2>';
        modalContent     += contentHtml;
        modalContent     += '<button class="close-modal" id="close-modal-unificacao">Fechar</button>';

        $modal.html( modalContent );

        $( 'body' ).append( $modal ).append( $overlay );
        $overlay.fadeIn( 150 );
        $modal
            .css( {
                opacity: 0,
                top: '40%',
                display: 'block'
            } )
            .animate(
                {
                    opacity: 1,
                    top: '50%'
                },
                150
            );

        $( '#close-modal-unificacao' ).off( 'click' ).on( 'click', closeModal );
        $overlay.off( 'click' ).on( 'click', closeModal );
    }

    /* ---------------------------------------------------------------------
     * Exibe uma notificação (estilo admin notice WP) dentro do modal.
     *
     * @param {string} message Mensagem a ser exibida.
     * @param {string} type    success|error|warning|info
     * ------------------------------------------------------------------ */
    function showModalNotice( message, type ) {
        if ( 'success' !== type && 'error' !== type && 'warning' !== type && 'info' !== type ) {
            type = 'info';
        }

        var noticeClass = 'notice-' + type;
        var $notice = $(
            '<div class="notice ' + noticeClass + '" style="margin-bottom:10px;"><p>' + message + '</p></div>'
        );

        // Remove notificações anteriores
        $modal.find( '.notice' ).remove();

        // Insere no topo do modal
        $modal.prepend( $notice );

        // Anima
        $notice.hide().fadeIn( 200 );
    }

    /* ---------------------------------------------------------------------
     * Coleta dados das linhas selecionadas (checkbox marcados).
     *
     * Retorna array de objetos:
     * [ { postId: 123, perguntaIndex: 0, respostaTexto: "..." }, ... ]
     * ------------------------------------------------------------------ */
    function coletarLinhasSelecionadas() {
        var linhas = [];

        $( 'input[name="respostas_ids[]"]:checked' ).each( function() {
            var $row = $( this ).closest( 'tr.unificacao-tr' );
            var postId = parseInt( $( this ).val(), 10 );
            var perguntaIndex = parseInt( $row.data( 'pergunta-index' ), 10 );

            // Texto da resposta (coluna resposta)
            var resposta = $row.find( 'td.unificacao-resposta-column .tooltip' ).attr( 'title' ) ||
                $row.find( 'td.unificacao-resposta-column' ).text().trim();

            linhas.push({
                postId: postId,
                perguntaIndex: isNaN( perguntaIndex ) ? null : perguntaIndex,
                respostaTexto: resposta
            });
        } );

        return linhas;
    }

    /* ---------------------------------------------------------------------
     * IDs de respostas selecionadas (sem duplicados).
     * ------------------------------------------------------------------ */
    function coletarIdsSelecionados() {
        var ids = [];
        var map = {};

        coletarLinhasSelecionadas().forEach( function( linha ) {
            if ( linha.postId && ! map[ linha.postId ] ) {
                map[ linha.postId ] = true;
                ids.push( linha.postId );
            }
        } );

        return ids;
    }

    /* ---------------------------------------------------------------------
     * Índices de perguntas representados pelas linhas selecionadas (sem duplicados).
     * ------------------------------------------------------------------ */
    function coletarPerguntaIndicesSelecionados() {
        var indices = [];
        var map = {};

        coletarLinhasSelecionadas().forEach( function( linha ) {
            if ( linha.perguntaIndex !== null && ! map[ linha.perguntaIndex ] ) {
                map[ linha.perguntaIndex ] = true;
                indices.push( linha.perguntaIndex );
            }
        } );

        return indices;
    }

    /* ---------------------------------------------------------------------
     * Coleta a lista de valores de unificação existentes (Coluna 2).
     *
     * Lê a tabela #unificacao-second-table.
     * Retorna array de objetos: [{ valor: 'Grupo1', label: 'Grupo1 (10)' }, ...]
     * Ignora linhas onde o valor é vazio.
     * ------------------------------------------------------------------ */
    function coletarUnificacoesExistentes() {
        var unificacoes = [];

        $( '#unificacao-second-table tbody tr' ).each( function() {
            var $tr = $( this );
            var valor = $tr.data( 'resposta-key' );

            if ( ! valor || $.trim( valor ) === '' ) {
                return;
            }

            var texto = $tr.find( 'td:first .tooltip' ).attr( 'title' ) || valor;
            var contagem = $tr.find( 'td:nth-child(2)' ).text().trim();

            unificacoes.push({
                valor: valor,
                label: texto + ' (' + contagem + ')'
            });
        } );

        return unificacoes;
    }

    /* ---------------------------------------------------------------------
     * Envia a unificação para o servidor (criar ou aplicar existente).
     *
     * @param {string} valorUnificacao   Texto a ser gravado no campo 'unificada'.
     * @param {Array}  respostasIds      Array de post IDs (linhas selecionadas).
     * @param {Array}  perguntaIndices   Array de índices de perguntas a atualizar.
     * ------------------------------------------------------------------ */
    function salvarUnificacao( valorUnificacao, linhasSelecionadas ) {
        if ( ! valorUnificacao || ! valorUnificacao.length ) {
            showModalNotice( 'Informe um valor para unificação.', 'error' );
            return;
        }

        if ( ! linhasSelecionadas || ! linhasSelecionadas.length ) {
            showModalNotice( 'Selecione ao menos uma resposta.', 'error' );
            return;
        }


        
        $.ajax({
            url: VSU.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'vs_update_resposta_unificada',
                linhas: JSON.stringify( linhasSelecionadas ),
                nova_resposta_unificada: valorUnificacao,
                votacao_id: VSU.votacao_id,
                nonce: VSU.nonce
            },
            success: function( response ) {
                if ( response && response.success ) {
                    showModalNotice( 'Respostas unificadas com sucesso! Atualizando...', 'success' );

                    // Recarrega a página após breve atraso para permitir leitura do aviso.
                    setTimeout( function() {
                        window.location.reload();
                    }, 1200 );
                } else {
                    var msg = response && response.data ? response.data : 'Erro desconhecido.';
                    showModalNotice( 'Erro ao salvar unificação: ' + msg, 'error' );
                }
            },
            error: function( xhr, status, error ) {
                showModalNotice( 'Erro na requisição AJAX: ' + error, 'error' );
            }
        });
    }

    /* ---------------------------------------------------------------------
     * Abre o modal de Unificação com base nas linhas selecionadas.
     * Inclui:
     *  - Lista das respostas selecionadas
     *  - Campo para criar nova unificação
     *  - Select para aplicar unificação existente
     * ------------------------------------------------------------------ */
    function openUnificacaoModal() {
        var linhasSelecionadas = coletarLinhasSelecionadas();

        var contentHtml = '';
        if ( ! linhasSelecionadas.length ) {
            contentHtml = '<p>Nenhuma resposta selecionada.</p>';
            openModal( 'Unificação de Respostas', contentHtml );
            return;
        }

        // Lista de respostas selecionadas
        contentHtml += '<div style="max-height: 200px; overflow-y: auto; border: 1px solid #ccc; padding: 8px;">';
        linhasSelecionadas.forEach( function( linha ) {
            contentHtml += '<p style="margin: 0 0 8px;">' + $( '<div>' ).text( linha.respostaTexto ).html() + '</p>';
        } );
        contentHtml += '</div>';

        // Campo para criar nova Resposta Unificada
        contentHtml += '<h3 style="margin-top:15px;">Criar nova Resposta unificada:</h3>';
        contentHtml += '<input type="text" id="nova-unificacao-input" style="width:100%; padding:6px;" placeholder="Digite uma nova unificação">';
        contentHtml += '<button id="btn-criar-unificacao" class="button button-primary" style="margin-top:10px;">Unificar</button>';

        // Lista de unificações existentes (Coluna 2)
        var unificacoes = coletarUnificacoesExistentes();
        if ( unificacoes.length > 0 ) {
            contentHtml += '<h3 style="margin-top:20px;">Selecionar Resposta Unificada existente:</h3>';
            contentHtml += '<select id="nova-resposta-unificada" style="width:100%; padding:6px;">';
            unificacoes.forEach( function( u ) {
                contentHtml += '<option value="' + $( '<div>' ).text( u.valor ).html() + '">' + $( '<div>' ).text( u.label ).html() + '</option>';
            } );
            contentHtml += '</select>';
            contentHtml += '<button id="btn-confirmar-unificacao" class="button button-secondary" style="margin-top:10px;">Aplicar Unificação</button>';
        }

        openModal( 'Unificação de Respostas', contentHtml );

        // Clique: Criar nova unificação
        $( '#btn-criar-unificacao' ).off( 'click' ).on( 'click', function() {
            var novaUnificacao = $( '#nova-unificacao-input' ).val().trim();
            salvarUnificacao( novaUnificacao, linhasSelecionadas );
        } );

        // Enter dentro do campo cria
        $( '#nova-unificacao-input' ).on( 'keypress', function( evt ) {
            if ( evt.which === 13 ) {
                evt.preventDefault();
                $( '#btn-criar-unificacao' ).trigger( 'click' );
            }
        } );

        // Clique: Aplicar unificação existente
        $( '#btn-confirmar-unificacao' ).off( 'click' ).on( 'click', function() {
            var novaUnificacao = $( '#nova-resposta-unificada' ).val();
            salvarUnificacao( novaUnificacao, linhasSelecionadas );
        } );
    }

    /* ---------------------------------------------------------------------
     * Eventos nos botões "Unificação" (topo e base da Coluna 1)
     * ------------------------------------------------------------------ */
    $( '#btn-unificacao-top, #btn-unificacao-bottom' ).off( 'click' ).on( 'click', function() {
        openUnificacaoModal();
    } );

    /* ---------------------------------------------------------------------
     * Destacar linha ao selecionar checkbox
     * ------------------------------------------------------------------ */
    $( document ).on( 'change', 'input[name="respostas_ids[]"]', function() {
        var $row = $( this ).closest( 'tr.unificacao-tr' );
        if ( this.checked ) {
            $row.addClass( 'selected' );
        } else {
            $row.removeClass( 'selected' );
        }
    } );

    /* ---------------------------------------------------------------------
     * "Selecionar todos"
     * ------------------------------------------------------------------ */
    $( '#select-all' ).on( 'change', function() {
        var checked = this.checked;
        $( 'input[name="respostas_ids[]"]' )
            .prop( 'checked', checked )
            .trigger( 'change' );
    } );

    /* ---------------------------------------------------------------------
     * "Ver todos" (Coluna 2): mostra todas as respostas ligadas a um grupo unificado
     * ------------------------------------------------------------------ */
    $( document ).off( 'click' ).on( 'click', '.unificacao-ver-todos', function( e ) {
        e.preventDefault();

        var respostaKey = $( this ).data( 'resposta-key' ) || '';

        openModal( 'Carregando respostas...', '<p>Por favor aguarde...</p>' );
        $.ajax({
            url: VSU.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'vs_get_respostas_unificadas',
                votacao_id: VSU.votacao_id,
                resposta_unificada: respostaKey,
                nonce: VSU.nonce
            },
            success: function( response ) {
                if ( response && response.success ) {
                    var data   = response.data.responses || response.data; // fallback se handler antigo
                    var counts = response.data.counts    || {};

                    var html = '<table class="unificacao-table" style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
                    html    += '<thead><tr>' +
                                '<th>Usuário</th>' +
                                '<th>Pergunta</th>' +
                                '<th>Resposta</th>' +
                                '<th>Resposta Unificada</th>' +
                                '<th>Contagem</th>' +
                               '</tr></thead><tbody>';

                    // Função escape
                    function esc( text ) {
                        return $( '<div>' ).text( text ).html();
                    }

                    $.each( data, function( i, item ) {
                        var count = counts[ item.resposta ] || '';
                        html += '<tr>' +
                                    '<td>' + esc( item.usuario ) + '</td>' +
                                    '<td>' + esc( item.pergunta ) + '</td>' +
                                    '<td>' + esc( item.resposta ) + '</td>' +
                                    '<td>' + esc( item.resposta_unificada ) + '</td>' +
                                    '<td style="text-align:center;">' + esc( count ) + '</td>' +
                                '</tr>';
                    } );

                    html += '</tbody></table>';

                    openModal( 'Respostas para a Unificação Selecionada', html );
                } else {
                    var msg = response && response.data ? response.data : 'Erro desconhecido.';
                    openModal( 'Erro', '<p>' + msg + '</p>' );
                }
            },
            error: function( xhr, status, error ) {
                openModal( 'Erro', '<p>Erro na requisição AJAX: ' + error + '</p>' );
            }
        });
    } );

} );

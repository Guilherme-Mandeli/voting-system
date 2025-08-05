/**
 * Inicialização principal dos módulos do admin
 */
(function($) {
    'use strict';

    // Criar namespace VSAdmin se não existir
    window.VSAdmin = window.VSAdmin || {};

    // Variável global para currentQuestion (compatibilidade com código original)
    let currentQuestion = null;

    // Função global para compatibilidade
    function updateImportedAnswersTable() {
        if (window.VSAdmin.ImportedAnswers && window.VSAdmin.ImportedAnswers.updateTable) {
            window.VSAdmin.ImportedAnswers.updateTable();
        }
    }

    // Expor funções globais para compatibilidade
    window.updateImportedAnswersTable = updateImportedAnswersTable;
    window.currentQuestion = currentQuestion;

    // Inicialização quando o documento estiver pronto
    $(document).ready(function() {
        console.log('VSAdmin: Iniciando módulos...');

        // Inicializar todos os módulos na ordem correta
        try {
            // Utils - sempre primeiro
            if (window.VSAdmin.Utils && typeof window.VSAdmin.Utils.init === 'function') {
                window.VSAdmin.Utils.init();
                console.log('VSAdmin: Utils inicializado');
            }
            
            // FieldTypeHandler - segundo (gerencia tipos de campo)
            if (window.VSAdmin.FieldTypeHandler && typeof window.VSAdmin.FieldTypeHandler.init === 'function') {
                window.VSAdmin.FieldTypeHandler.init();
                console.log('VSAdmin: FieldTypeHandler inicializado');
            }
            
            // ImportedAnswers - terceiro (depende do FieldTypeHandler)
            if (window.VSAdmin.ImportedAnswers && typeof window.VSAdmin.ImportedAnswers.init === 'function') {
                window.VSAdmin.ImportedAnswers.init();
                console.log('VSAdmin: ImportedAnswers inicializado');
            }
            
            // VotingModal - último (depende dos outros módulos)
            if (window.VSAdmin.VotingModal && typeof window.VSAdmin.VotingModal.init === 'function') {
                window.VSAdmin.VotingModal.init();
                console.log('VSAdmin: VotingModal inicializado');
            }

            console.log('VSAdmin: Todos os módulos inicializados com sucesso');

            // Aguardar um momento para garantir que todos os event handlers foram registrados
            setTimeout(function() {
                // Inicializar campos existentes do tipo imported_vote
                $('.vs-tipo-campo').each(function() {
                    if ($(this).val() === 'imported_vote') {
                        currentQuestion = $(this).closest('.vs-pergunta');
                        if (window.VSAdmin.ImportedAnswers) {
                            window.VSAdmin.ImportedAnswers.setCurrentQuestion(currentQuestion);
                            window.VSAdmin.ImportedAnswers.updateTable();
                            console.log('VSAdmin: Campo imported_vote inicializado');
                        }
                    }
                });
            }, 100);

        } catch (error) {
            console.error('VSAdmin: Erro durante inicialização dos módulos:', error);
        }
    });

})(jQuery);

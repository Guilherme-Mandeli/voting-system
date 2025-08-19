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
        // Inicializar todos os módulos na ordem correta
        try {
            // Utils - sempre primeiro
            if (window.VSAdmin.Utils && typeof window.VSAdmin.Utils.init === 'function') {
                window.VSAdmin.Utils.init();
            }
            
            // FieldTypeHandler - segundo (gerencia tipos de campo)
            if (window.VSAdmin.FieldTypeHandler && typeof window.VSAdmin.FieldTypeHandler.init === 'function') {
                window.VSAdmin.FieldTypeHandler.init();
            }
            
            // ImportedAnswers - terceiro (depende do FieldTypeHandler)
            if (window.VSAdmin.ImportedAnswers && typeof window.VSAdmin.ImportedAnswers.init === 'function') {
                window.VSAdmin.ImportedAnswers.init();
            }
            
            // VotingModal - último (depende dos outros módulos)
            if (window.VSAdmin.VotingModal && typeof window.VSAdmin.VotingModal.init === 'function') {
                window.VSAdmin.VotingModal.init();
            }

        } catch (error) {
            console.error('VSAdmin: Erro durante inicialização dos módulos:', error);
        }
    });

})(jQuery);

# Especificação Técnica - PUSH vs SUBSTITUIÇÃO

## Visão Geral

Implementação de lógica inteligente para decidir entre PUSH (adicionar) e SUBSTITUIÇÃO (sobrescrever) de dados importados baseada no uso ativo de votações.

## Decisão PUSH vs SUBSTITUIÇÃO

### Critério Principal
```javascript
function shouldUsePushStrategy(voteId) {
  return isVotingInUse(voteId);
}
```

### Definição de "Votação em Uso"
Uma votação é considerada "em uso" quando:
1. Existem valores ativos no DOM (opções visíveis na interface)
2. Estes valores têm interseção com dados no JSON
3. Indica que o usuário já trabalhou com esta votação

## Assinaturas de Funções

### 1. Helpers de Dados JSON
```javascript
/**
 * Obtém dados JSON atuais do campo oculto
 * @returns {ImportedAnswersData} Dados parseados ou estrutura vazia
 */
function getCurrentJsonData() {
  // Implementação
}

/**
 * Define dados JSON no campo oculto
 * @param {ImportedAnswersData} data - Dados a serem salvos
 */
function setCurrentJsonData(data) {
  // Implementação
}
```

### 2. Detecção de Uso Ativo
```javascript
/**
 * Obtém valores ativos do DOM para uma votação específica
 * @param {string} voteId - ID da votação
 * @returns {string[]} Array de valores ativos
 */
function getActiveImportedValues(voteId) {
  // Implementação
}

/**
 * Verifica se uma votação está em uso ativo
 * @param {string} voteId - ID da votação
 * @returns {boolean} True se a votação está em uso
 */
function isVotingInUse(voteId) {
  // Implementação
}
```

### 3. Lógica de Merge
```javascript
/**
 * Mescla dados de votação usando estratégia PUSH ou SUBSTITUIÇÃO
 * @param {ImportedAnswersData} existingData - Dados existentes
 * @param {Question[]} newQuestions - Novas perguntas a serem mescladas
 * @param {string} strategy - 'push' ou 'replace'
 * @returns {ImportedAnswersData} Dados mesclados
 */
function mergeVotingData(existingData, newQuestions, strategy) {
  // Implementação
}

/**
 * Função principal que orquestra a decisão e merge
 * @param {Question[]} newQuestions - Novas perguntas a importar
 * @returns {ImportedAnswersData} Dados finais mesclados
 */
function updateImportedAnswers(newQuestions) {
  // Implementação
}
```

## Regras de Merge

### Estratégia PUSH
- **Objetivo**: Preservar trabalho do usuário
- **Questions**: Mantém existentes, adiciona apenas novas
- **Imported Items**: Preserva todos existentes, adiciona novos únicos
- **Manual Items**: Sempre preservados (nunca afetados por importação)

### Estratégia SUBSTITUIÇÃO
- **Objetivo**: Atualizar com dados mais recentes
- **Questions**: Substitui questions existentes do mesmo `vote_id`
- **Imported Items**: Remove items do `vote_id` e adiciona novos
- **Manual Items**: Sempre preservados

## Feature Flag

### Configuração
```javascript
const VS_IMPORT_MERGE_STRATEGY = {
  enabled: true, // Feature flag principal
  fallbackToLegacy: true, // Fallback em caso de erro
  debugMode: false // Logs detalhados
};
```

### Implementação com Fallback
```javascript
function safeUpdateImportedAnswers(newQuestions) {
  if (!VS_IMPORT_MERGE_STRATEGY.enabled) {
    return legacyImportBehavior(newQuestions);
  }
  
  try {
    return updateImportedAnswers(newQuestions);
  } catch (error) {
    console.error('Erro na nova lógica de importação:', error);
    
    if (VS_IMPORT_MERGE_STRATEGY.fallbackToLegacy) {
      return legacyImportBehavior(newQuestions);
    }
    
    throw error;
  }
}
```

## Integração com Sistema Existente

### Pontos de Integração
1. **voting-modal.js**: Substituir linha ~588 por chamada a `safeUpdateImportedAnswers`
2. **Eventos**: Manter compatibilidade com `vs:update-imported-table`
3. **UI Sync**: Garantir que `updateTable()` continue funcionando

### Compatibilidade
- Manter todas as interfaces públicas existentes
- Preservar comportamento de eventos
- Garantir que fallback funcione identicamente ao código atual
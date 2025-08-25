# Guia de Implementação - PUSH vs SUBSTITUIÇÃO

## Ordem de Implementação

### Fase 1: Fundação
1. **Helpers JSON** (`getCurrentJsonData`, `setCurrentJsonData`)
2. **Utilitários de Normalização** (`normalizeValue`, `groupByVoteId`, `upsertQuestions`)
3. **Detecção de Uso Ativo** (`getActiveImportedValues`, `isVotingInUse`)

### Fase 2: Lógica Core
4. **Função de Merge** (`mergeVotingData`)
5. **Orquestrador Principal** (`updateImportedAnswers`)
6. **Feature Flag e Fallback** (`safeUpdateImportedAnswers`)

### Fase 3: Integração
7. **Integração com voting-modal.js**
8. **Testes e Validação**
9. **Sincronização de Eventos e UI**

## Detalhes de Implementação

### 1. Helpers JSON (imported-answers.js)

```javascript
// Adicionar ao módulo VSAdmin.ImportedAnswers
getCurrentJsonData: function() {
    const $field = $('.vs-imported-answers');
    if (!$field.length) {
        return { manual_items: [], imported_items: [], questions: [], selected_questions: [] };
    }
    
    try {
        const jsonStr = $field.val() || '{}';
        const data = JSON.parse(jsonStr);
        
        // Garantir estrutura válida
        return {
            manual_items: Array.isArray(data.manual_items) ? data.manual_items : [],
            imported_items: Array.isArray(data.imported_items) ? data.imported_items : [],
            questions: Array.isArray(data.questions) ? data.questions : [],
            selected_questions: Array.isArray(data.selected_questions) ? data.selected_questions : []
        };
    } catch (e) {
        console.warn('JSON malformado no campo vs-imported-answers:', e);
        return { manual_items: [], imported_items: [], questions: [], selected_questions: [] };
    }
},

setCurrentJsonData: function(data) {
    const $field = $('.vs-imported-answers');
    if ($field.length) {
        $field.val(JSON.stringify(data));
    }
}
```

### 2. Utilitários de Normalização

```javascript
// Adicionar ao módulo VSAdmin.ImportedAnswers
normalizeValue: function(value) {
    return String(value || '').trim();
},

groupByVoteId: function(questions) {
    return questions.reduce((acc, q) => {
        if (q.vote_id) {
            acc[q.vote_id] = q;
        }
        return acc;
    }, {});
},

upsertQuestions: function(existing, newQuestions, strategy) {
    const existingMap = this.groupByVoteId(existing);
    
    newQuestions.forEach(newQ => {
        if (strategy === 'push' && existingMap[newQ.vote_id]) {
            // PUSH: manter existente
            return;
        }
        // REPLACE ou nova question: adicionar/substituir
        existingMap[newQ.vote_id] = newQ;
    });
    
    return Object.values(existingMap);
}
```

### 3. Detecção de Uso Ativo

```javascript
// Adicionar ao módulo VSAdmin.ImportedAnswers
getActiveImportedValues: function(voteId) {
    const activeValues = [];
    
    // Buscar por elementos DOM que representam valores ativos desta votação
    $('.vs-option-item').each(function() {
        const $item = $(this);
        const itemVoteId = $item.find('input[name*="vote_id"]').val();
        
        if (itemVoteId === voteId) {
            const realValue = $item.find('input[name*="real_value"]').val();
            const visualValue = $item.find('input[name*="visual_value"]').val();
            
            if (realValue || visualValue) {
                activeValues.push(this.normalizeValue(realValue));
                activeValues.push(this.normalizeValue(visualValue));
            }
        }
    });
    
    return [...new Set(activeValues)]; // Remove duplicatas
},

isVotingInUse: function(voteId) {
    const activeValues = this.getActiveImportedValues(voteId);
    if (activeValues.length === 0) {
        return false;
    }
    
    const currentData = this.getCurrentJsonData();
    const jsonValues = [];
    
    // Coletar valores do JSON para esta votação
    currentData.imported_items.forEach(item => {
        if (item.voteId === voteId) {
            jsonValues.push(this.normalizeValue(item.realValue));
            jsonValues.push(this.normalizeValue(item.visualValue));
        }
    });
    
    // Verificar interseção
    return activeValues.some(activeVal => 
        jsonValues.some(jsonVal => jsonVal === activeVal)
    );
}
```

### 4. Lógica de Merge

```javascript
// Adicionar ao módulo VSAdmin.ImportedAnswers
mergeVotingData: function(existingData, newQuestions, strategy) {
    const result = {
        manual_items: [...existingData.manual_items], // Sempre preservar
        imported_items: [...existingData.imported_items],
        questions: [...existingData.questions],
        selected_questions: [...existingData.selected_questions]
    };
    
    newQuestions.forEach(newQuestion => {
        const voteId = newQuestion.vote_id;
        
        if (strategy === 'replace') {
            // SUBSTITUIÇÃO: remover dados antigos desta votação
            result.imported_items = result.imported_items.filter(item => item.voteId !== voteId);
            result.questions = result.questions.filter(q => q.vote_id !== voteId);
        }
        
        // Adicionar nova question
        const existingQuestionIndex = result.questions.findIndex(q => q.vote_id === voteId);
        if (existingQuestionIndex >= 0 && strategy === 'replace') {
            result.questions[existingQuestionIndex] = newQuestion;
        } else if (existingQuestionIndex < 0) {
            result.questions.push(newQuestion);
        }
        
        // Adicionar novos imported_items
        if (newQuestion.imported_answers) {
            newQuestion.imported_answers.forEach((answer, index) => {
                const newItem = {
                    realValue: this.normalizeValue(answer.value),
                    visualValue: this.normalizeValue(answer.display || answer.value),
                    voteId: voteId,
                    questionIndex: index
                };
                
                // Verificar duplicata
                const isDuplicate = result.imported_items.some(existing => 
                    existing.realValue === newItem.realValue &&
                    existing.visualValue === newItem.visualValue &&
                    existing.voteId === newItem.voteId &&
                    existing.questionIndex === newItem.questionIndex
                );
                
                if (!isDuplicate) {
                    result.imported_items.push(newItem);
                }
            });
        }
    });
    
    return result;
},

updateImportedAnswers: function(newQuestions) {
    const existingData = this.getCurrentJsonData();
    const voteIds = [...new Set(newQuestions.map(q => q.vote_id))];
    
    let mergedData = existingData;
    
    voteIds.forEach(voteId => {
        const questionsForVote = newQuestions.filter(q => q.vote_id === voteId);
        const strategy = this.isVotingInUse(voteId) ? 'push' : 'replace';
        
        if (window.VS_IMPORT_MERGE_STRATEGY && window.VS_IMPORT_MERGE_STRATEGY.debugMode) {
            console.log(`Vote ${voteId}: usando estratégia ${strategy}`);
        }
        
        mergedData = this.mergeVotingData(mergedData, questionsForVote, strategy);
    });
    
    this.setCurrentJsonData(mergedData);
    return mergedData;
}
```

### 5. Feature Flag e Fallback

```javascript
// Adicionar ao início do arquivo imported-answers.js
window.VS_IMPORT_MERGE_STRATEGY = {
    enabled: true,
    fallbackToLegacy: true,
    debugMode: false
};

// Adicionar ao módulo VSAdmin.ImportedAnswers
safeUpdateImportedAnswers: function(newQuestions) {
    if (!window.VS_IMPORT_MERGE_STRATEGY.enabled) {
        return this.legacyImportBehavior(newQuestions);
    }
    
    try {
        return this.updateImportedAnswers(newQuestions);
    } catch (error) {
        console.error('Erro na nova lógica de importação:', error);
        
        if (window.VS_IMPORT_MERGE_STRATEGY.fallbackToLegacy) {
            console.warn('Usando fallback para comportamento legacy');
            return this.legacyImportBehavior(newQuestions);
        }
        
        throw error;
    }
},

legacyImportBehavior: function(newQuestions) {
    // Implementar comportamento atual como fallback
    // (código extraído do voting-modal.js atual)
}
```

### 6. Integração com voting-modal.js

```javascript
// Em voting-modal.js, substituir a linha ~588 por:
if (window.VSAdmin && window.VSAdmin.ImportedAnswers && 
    typeof window.VSAdmin.ImportedAnswers.safeUpdateImportedAnswers === 'function') {
    
    // Nova lógica
    const mergedData = window.VSAdmin.ImportedAnswers.safeUpdateImportedAnswers(questionsToImport);
    
} else {
    // Fallback para código atual
    // ... código existente ...
}
```

## Checklist de Implementação

- [ ] Implementar helpers JSON
- [ ] Implementar utilitários de normalização
- [ ] Implementar detecção de uso ativo
- [ ] Implementar lógica de merge
- [ ] Implementar orquestrador principal
- [ ] Implementar feature flag e fallback
- [ ] Integrar com voting-modal.js
- [ ] Testar todos os casos de uso
- [ ] Validar eventos e sincronização UI
- [ ] Documentar configurações e troubleshooting

## Troubleshooting

### Problemas Comuns
1. **JSON malformado**: Verificar logs do console
2. **Performance lenta**: Otimizar queries DOM
3. **Dados perdidos**: Verificar estratégia de merge
4. **UI dessincronizada**: Verificar eventos `vs:update-imported-table`

### Debug
```javascript
// Ativar modo debug
window.VS_IMPORT_MERGE_STRATEGY.debugMode = true;

// Verificar dados atuais
console.log(window.VSAdmin.ImportedAnswers.getCurrentJsonData());

// Verificar uso ativo
console.log(window.VSAdmin.ImportedAnswers.isVotingInUse('vote_123'));
```
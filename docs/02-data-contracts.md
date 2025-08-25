# Contratos de Dados - Sistema de Importação

## Estrutura JSON Principal

### Schema Base
```typescript
interface ImportedAnswersData {
  manual_items: ManualItem[];
  imported_items: ImportedItem[];
  questions: Question[];
  selected_questions: SelectedQuestion[];
}

interface ManualItem {
  realValue: string;
  visualValue: string;
}

interface ImportedItem {
  realValue: string;
  visualValue: string;
  voteId: string;
  questionIndex: number;
}

interface Question {
  vote_id: string;
  vote_title: string;
  event_id: string;
  question_source: string;
  question_index: number;
  imported_answers: Answer[];
}

interface Answer {
  value: string;
  display: string;
}
```

## Invariantes do Sistema

### 1. Estrutura Sempre Válida
- `manual_items`, `imported_items`, `questions`, `selected_questions` devem sempre ser arrays
- Campos ausentes são inicializados como arrays vazios
- JSON malformado é tratado como estrutura vazia

### 2. Unicidade de Dados
- **manual_items**: Únicos por combinação `(realValue, visualValue)`
- **imported_items**: Únicos por combinação `(realValue, visualValue, voteId, questionIndex)`
- **questions**: Únicos por `vote_id`

### 3. Integridade Referencial
- `imported_items` devem referenciar `questions` existentes via `voteId`
- `questionIndex` deve ser válido dentro do contexto da pergunta

## Regras de Normalização

### 1. Valores de Resposta
```javascript
function normalizeValue(value) {
  return String(value || '').trim();
}
```

### 2. Agrupamento por Vote ID
```javascript
function groupByVoteId(questions) {
  return questions.reduce((acc, q) => {
    acc[q.vote_id] = q;
    return acc;
  }, {});
}
```

### 3. Upsert de Perguntas
```javascript
function upsertQuestions(existing, newQuestions) {
  const existingMap = groupByVoteId(existing);
  
  newQuestions.forEach(newQ => {
    existingMap[newQ.vote_id] = newQ; // Substitui ou adiciona
  });
  
  return Object.values(existingMap);
}
```

## Política de Duplicatas

### Critérios de Duplicata
1. **Manual Items**: `realValue` + `visualValue`
2. **Imported Items**: `realValue` + `visualValue` + `voteId` + `questionIndex`
3. **Questions**: `vote_id`

### Estratégia de Resolução
- **PUSH**: Mantém dados existentes, adiciona apenas novos
- **SUBSTITUIÇÃO**: Substitui dados existentes por novos

## Validação de Dados

### Validadores Obrigatórios
```javascript
function validateImportedAnswersData(data) {
  const errors = [];
  
  if (!Array.isArray(data.manual_items)) {
    errors.push('manual_items deve ser um array');
  }
  
  if (!Array.isArray(data.imported_items)) {
    errors.push('imported_items deve ser um array');
  }
  
  if (!Array.isArray(data.questions)) {
    errors.push('questions deve ser um array');
  }
  
  return errors;
}
```
# Inventário de Código - Sistema de Importação de Respostas

## Arquivos Principais

### voting-modal.js
**Localização**: `e:\Projects\voting-system\assets\js\admin\voting-modal.js`

**Responsabilidades**:
- Importação em lote de perguntas de votação
- Mesclagem inteligente de dados existentes
- Escrita principal no campo `.vs-imported-answers` (linha ~662)
- Integração com modal de importação

**Pontos Críticos**:
- `importSelected()` (linha ~588): Função principal de importação
- Mesclagem de `existingData` com `newQuestions`
- Filtragem de perguntas antigas do mesmo `vote_id`
- Escrita final: `$importedAnswersField.val(answersJson)`

### imported-answers.js
**Localização**: `e:\Projects\voting-system\assets\js\admin\imported-answers.js`

**Responsabilidades**:
- Gerenciamento da tabela de respostas importadas
- Adição manual de opções (`addSelected`)
- Remoção de opções (`removeOption`)
- Atualização da interface (`updateTable`, `_executeUpdateTable`)

**Pontos Críticos**:
- `addSelected()` (linha ~585): Escrita após adição manual
- `removeOption()` (linha ~690): Escrita após remoção
- Manipulação de `manual_items` e `imported_items`

### field-type-handler.js
**Localização**: `e:\Projects\voting-system\assets\js\admin\field-type-handler.js`

**Responsabilidades**:
- Limpeza do campo (linha 125): `.vs-imported-answers.val('')`

## Pontos de Escrita Identificados

1. **voting-modal.js:~662** - Importação em lote
2. **imported-answers.js:~585** - Adição manual
3. **imported-answers.js:~690** - Remoção de opções
4. **field-type-handler.js:125** - Limpeza do campo

## Estrutura JSON Atual

```json
{
  "manual_items": [],
  "imported_items": [],
  "questions": [],
  "selected_questions": []
}
```

## Fluxos Identificados

### Fluxo de Importação (voting-modal.js)
1. Usuário seleciona perguntas no modal
2. `importSelected()` é chamada
3. Dados existentes são obtidos e validados
4. Novas perguntas são processadas e unificadas
5. Mesclagem inteligente remove duplicatas por `vote_id`
6. Dados são escritos no campo oculto
7. Interface é atualizada

### Fluxo de Adição Manual (imported-answers.js)
1. Usuário seleciona opções na tabela
2. `addSelected()` é chamada
3. Dados atuais são obtidos e validados
4. Verificação de duplicatas por `realValue` e `visualValue`
5. Novos elementos DOM são criados
6. Dados são salvos no campo
7. Interface é atualizada

### Fluxo de Remoção (imported-answers.js)
1. Usuário clica em remover opção
2. `removeOption()` é chamada
3. Dados são obtidos e item é localizado
4. Arrays `manual_items` e `imported_items` são atualizados
5. Dados modificados são salvos
6. Elemento DOM é removido
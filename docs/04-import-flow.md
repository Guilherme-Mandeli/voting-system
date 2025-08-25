# Fluxo de Importação - Documentação Completa

## Fluxo Atual (Legacy)

### 1. Importação via Modal (voting-modal.js)
```mermaid
flowchart TD
    A[Usuário seleciona perguntas] --> B[importSelected() chamada]
    B --> C[Obter dados existentes do campo]
    C --> D[Validar e inicializar estrutura]
    D --> E[Processar novas perguntas]
    E --> F[Unificar respostas]
    F --> G[Filtrar perguntas antigas do mesmo vote_id]
    G --> H[Concatenar novas perguntas]
    H --> I[Escrever JSON no campo oculto]
    I --> J[Fechar modal e atualizar UI]
```

### 2. Adição Manual (imported-answers.js)
```mermaid
flowchart TD
    A[Usuário seleciona opções na tabela] --> B[addSelected() chamada]
    B --> C[Obter dados atuais]
    C --> D[Verificar duplicatas]
    D --> E[Criar elementos DOM]
    E --> F[Atualizar arrays manual_items/imported_items]
    F --> G[Salvar JSON]
    G --> H[Atualizar interface]
```

## Novo Fluxo (PUSH vs SUBSTITUIÇÃO)

### 1. Fluxo Principal
```mermaid
flowchart TD
    A[Novas perguntas a importar] --> B[updateImportedAnswers()]
    B --> C[getCurrentJsonData()]
    C --> D[Para cada vote_id em newQuestions]
    D --> E{isVotingInUse(voteId)?}
    E -->|Sim| F[Estratégia PUSH]
    E -->|Não| G[Estratégia SUBSTITUIÇÃO]
    F --> H[mergeVotingData(..., 'push')]
    G --> I[mergeVotingData(..., 'replace')]
    H --> J[setCurrentJsonData()]
    I --> J
    J --> K[Atualizar UI]
```

### 2. Detecção de Uso Ativo
```mermaid
flowchart TD
    A[isVotingInUse(voteId)] --> B[getActiveImportedValues(voteId)]
    B --> C[Obter valores do DOM]
    C --> D[Obter valores do JSON]
    D --> E{Há interseção?}
    E -->|Sim| F[return true]
    E -->|Não| G[return false]
```

### 3. Merge com Estratégia PUSH
```mermaid
flowchart TD
    A[mergeVotingData(..., 'push')] --> B[Preservar questions existentes]
    B --> C[Adicionar apenas novas questions]
    C --> D[Preservar imported_items existentes]
    D --> E[Adicionar novos imported_items únicos]
    E --> F[Sempre preservar manual_items]
    F --> G[return dados mesclados]
```

### 4. Merge com Estratégia SUBSTITUIÇÃO
```mermaid
flowchart TD
    A[mergeVotingData(..., 'replace')] --> B[Remover questions do mesmo vote_id]
    B --> C[Adicionar novas questions]
    C --> D[Remover imported_items do vote_id]
    D --> E[Adicionar novos imported_items]
    E --> F[Sempre preservar manual_items]
    F --> G[return dados mesclados]
```

## Casos de Uso

### Caso 1: Primeira Importação
- **Situação**: Campo vazio ou sem dados da votação
- **Decisão**: SUBSTITUIÇÃO (não há dados a preservar)
- **Resultado**: Dados importados normalmente

### Caso 2: Re-importação sem Uso
- **Situação**: Dados existem mas usuário não trabalhou com eles
- **Decisão**: SUBSTITUIÇÃO
- **Resultado**: Dados antigos são substituídos pelos novos

### Caso 3: Re-importação com Uso Ativo
- **Situação**: Usuário já adicionou/modificou opções da votação
- **Decisão**: PUSH
- **Resultado**: Dados existentes preservados, novos adicionados

### Caso 4: Importação Mista
- **Situação**: Múltiplas votações, algumas em uso, outras não
- **Decisão**: PUSH para votações em uso, SUBSTITUIÇÃO para outras
- **Resultado**: Comportamento híbrido por votação

## Pontos de Atenção

### 1. Performance
- `getActiveImportedValues()` deve ser otimizada para DOM queries
- Cache de resultados quando possível
- Evitar re-parsing desnecessário do JSON

### 2. Robustez
- Sempre validar estrutura JSON
- Fallback para comportamento legacy em caso de erro
- Logs detalhados em modo debug

### 3. Compatibilidade
- Manter eventos `vs:update-imported-table`
- Preservar todas as interfaces públicas
- Garantir que `updateTable()` continue funcionando

### 4. Testing
- Testar todos os casos de uso identificados
- Validar comportamento de fallback
- Verificar performance com grandes volumes de dados
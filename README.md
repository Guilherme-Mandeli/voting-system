# 🗳️ Voting System - Plugin WordPress

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-2.250726.2-orange.svg)](https://github.com/guilhermemandeli/voting-system/releases)

Sistema completo de votações personalizado para WordPress com interface administrativa avançada, shortcodes flexíveis e sistema inteligente de unificação de respostas.

## 📋 Índice

- [Características](#-características)
- [Requisitos](#-requisitos)
- [Instalação](#-instalação)
- [Configuração](#-configuração)
- [Como Usar](#-como-usar)
- [Shortcodes](#-shortcodes)
- [Funcionalidades Administrativas](#-funcionalidades-administrativas)
- [Sistema de Unificação](#-sistema-de-unificação)
- [Estrutura do Projeto](#-estrutura-do-projeto)
- [Desenvolvimento](#-desenvolvimento)
- [FAQ](#-faq)
- [Suporte](#-suporte)
- [Licença](#-licença)

## ✨ Características

### 🎯 Funcionalidades Principais
- **Votações Personalizadas**: Crie votações com perguntas dinâmicas e diferentes tipos de campos
- **Interface Administrativa**: Painel completo para gerenciamento de votações e resultados
- **Sistema de Unificação**: Agrupe respostas similares para análise estatística avançada
- **Shortcodes Flexíveis**: Integre votações em qualquer página ou post
- **Área do Usuário**: Painel personalizado para usuários gerenciarem suas votações
- **Feeds Públicos**: Exiba listas de votações com filtros avançados
- **Agendamento Automático**: Configure datas de início e fim das votações
- **Exportação CSV**: Exporte dados para análise externa
- **Responsivo**: Interface adaptada para desktop e mobile

### 🔧 Tipos de Campo Suportados
- ✅ Texto livre
- ✅ Seleção única (radio)
- ✅ Seleção múltipla (checkbox)
- ✅ Lista suspensa (select)
- ✅ Área de texto (textarea)

### 🛡️ Segurança
- Validação rigorosa de dados
- Proteção contra CSRF com nonces
- Sanitização de entrada e escape de saída
- Verificação de permissões de usuário

## 📋 Requisitos

### Requisitos Mínimos
- **WordPress**: 5.0 ou superior
- **PHP**: 7.4 ou superior
- **MySQL**: 5.6 ou superior
- **Memória PHP**: 128MB (recomendado: 256MB)

### Testado até
- **WordPress**: 6.4
- **PHP**: 8.2

## 🚀 Instalação

### Método 1: Upload Manual

1. **Baixe o plugin**
   ```bash
   git clone https://github.com/guilhermemandeli/voting-system.git
   ```

2. **Faça upload para WordPress**
   - Compacte a pasta `voting-system` em um arquivo ZIP
   - Acesse `Plugins > Adicionar Novo > Enviar Plugin`
   - Selecione o arquivo ZIP e clique em "Instalar Agora"

3. **Ative o plugin**
   - Vá para `Plugins > Plugins Instalados`
   - Encontre "Voting System" e clique em "Ativar"

### Método 2: FTP

1. **Upload via FTP**
   ```bash
   # Faça upload da pasta para:
   /wp-content/plugins/voting-system/
   ```

2. **Ative no WordPress**
   - Acesse o painel administrativo
   - Vá para `Plugins` e ative o "Voting System"

### Método 3: WP-CLI

```bash
# Clone o repositório
git clone https://github.com/guilhermemandeli/voting-system.git /path/to/wordpress/wp-content/plugins/voting-system

# Ative o plugin
wp plugin activate voting-system
```

## ⚙️ Configuração

### Configuração Inicial

1. **Após a ativação**, o plugin criará automaticamente:
   - Custom Post Type `votacoes`
   - Custom Post Type `votacao_resposta`
   - Taxonomia `eventos`
   - Páginas administrativas

2. **Verifique as permissões**:
   - Usuários com capacidade `manage_options` podem gerenciar votações
   - Configure permissões adicionais conforme necessário

3. **Configure permalinks**:
   - Vá para `Configurações > Links Permanentes`
   - Clique em "Salvar Alterações" para atualizar as regras

## 📖 Como Usar

### Criando uma Votação

1. **Acesse o menu administrativo**
   ```
   WordPress Admin > Votações > Adicionar Nova
   ```

2. **Configure a votação**:
   - **Título**: Nome da votação
   - **Descrição**: Contexto e instruções
   - **Código**: Identificador único (opcional)
   - **Ano**: Ano de referência
   - **Status**: `aberta`, `em-pausa`, ou `encerrada`

3. **Adicione perguntas**:
   - Use o metabox "Perguntas da Votação"
   - Configure tipo de campo e opções
   - Defina se a pergunta é obrigatória

4. **Configure datas** (opcional):
   - Data de início
   - Data de encerramento
   - Agendamento automático

5. **Publique a votação**

### Exibindo Votações

#### Formulário de Votação
```php
[vs_voting_form id="123"]
```

#### Feed de Votações
```php
[vs_votacoes_feed]
```

#### Feed da Página Inicial
```php
[vs_votacoes_home_feed]
```

#### Página de Agradecimento
```php
[vs_thank_you]
```

### Criando Página do Usuário

Para criar uma página onde usuários podem gerenciar suas votações (recomendado: `/votacoes`):

1. **Crie uma nova página** no WordPress
2. **Configure como restrita** para usuários logados
3. **Adicione os shortcodes da área do usuário**:

```php
[votacoes_usuario_ativas]
[votacoes_usuario_encerradas]
[votacoes_disponiveis]
```

## 🎨 Shortcodes

### Shortcodes Principais

#### `[vs_voting_form]`
Exibe o formulário de uma votação específica.

**Parâmetros:**
- `id` (obrigatório): ID da votação

**Exemplo:**
```php
[vs_voting_form id="123"]
```

**Funcionalidades:**
- Validação automática de campos
- Prevenção de votos duplicados
- Edição de votos (se habilitada)
- Redirecionamento automático

#### `[vs_votacoes_feed]`
Exibe uma lista filtrada de votações.

**Parâmetros:**
- `ano`: Filtrar por ano
- `status`: Filtrar por status
- `evento`: Filtrar por evento
- `posts_per_page`: Número de itens por página

**Exemplo:**
```php
[vs_votacoes_feed ano="2024" status="aberta" posts_per_page="10"]
```

#### `[vs_votacoes_home_feed]`
Feed principal com cards visuais agrupados.

**Exemplo:**
```php
[vs_votacoes_home_feed]
```

#### `[vs_thank_you]`
Página de agradecimento pós-votação.

**Exemplo:**
```php
[vs_thank_you]
```

### 🆕 Shortcodes da Área do Usuário

#### `[votacoes_usuario_ativas]`
Lista votações que o usuário respondeu e ainda podem ser editadas.

**Características:**
- ✅ Restrição para usuários logados
- ✅ Exibe título, data e resumo das respostas
- ✅ Botão "Editar Voto" para cada votação
- ✅ Filtra apenas votações ativas
- ✅ Design responsivo com cards

**Exemplo:**
```php
[votacoes_usuario_ativas]
```

**Exibe para cada votação:**
- Título da votação
- Data da votação
- Data da resposta
- Resumo das respostas do usuário
- Botão "Editar Voto"

#### `[votacoes_usuario_encerradas]`
Lista votações que o usuário participou e já estão encerradas.

**Características:**
- ✅ Restrição para usuários logados
- ✅ Exibe votações finalizadas
- ✅ Status "Encerrada" visível
- ✅ Botão "Ver Respostas" opcional
- ✅ Histórico completo do usuário

**Exemplo:**
```php
[votacoes_usuario_encerradas]
```

**Exibe para cada votação:**
- Título da votação
- Status: "Encerrada"
- Data da votação
- Data da resposta
- Resumo das respostas do usuário
- Botão "Ver Respostas"

#### `[votacoes_disponiveis]`
Lista votações em aberto que o usuário ainda não participou.

**Características:**
- ✅ Restrição para usuários logados
- ✅ Filtra votações não respondidas
- ✅ Exibe prazo de encerramento
- ✅ Botão "Participar" destacado
- ✅ Descrição da votação

**Exemplo:**
```php
[votacoes_disponiveis]
```

**Exibe para cada votação:**
- Título da votação
- Prazo de encerramento
- Breve descrição
- Botão "Participar"

### 🎨 Personalização dos Shortcodes da Área do Usuário

Os novos shortcodes incluem CSS integrado e responsivo. Para personalizar:

```css
/* Personalizar cards das votações */
.vs-votacao-card {
    /* Seus estilos personalizados */
}

/* Personalizar botões */
.vs-btn-editar,
.vs-btn-participar,
.vs-btn-ver-respostas {
    /* Seus estilos personalizados */
}

/* Personalizar status */
.vs-status-ativa,
.vs-status-encerrada,
.vs-status-disponivel {
    /* Seus estilos personalizados */
}
```

### 🔐 Segurança dos Shortcodes

Todos os shortcodes da área do usuário incluem:

- **Verificação de login**: Exibe mensagem para usuários não logados
- **Sanitização de dados**: Todos os dados são sanitizados
- **Escape de saída**: Prevenção contra XSS
- **Validação de permissões**: Verificação de acesso

## 🔧 Funcionalidades Administrativas

### Painel de Resultados

Acesse `Votações > Resultados das Votações` para:

- **Visualizar estatísticas** de participação
- **Analisar respostas** individuais
- **Exportar dados** em formato CSV
- **Gerenciar unificações** de respostas

### Exportação de Dados

1. Acesse a página de resultados
2. Clique em "Exportar CSV"
3. Escolha o formato de exportação:
   - Respostas individuais
   - Dados unificados
   - Estatísticas gerais

### Sistema de Busca

- **Busca por usuário**: Encontre respostas específicas
- **Filtros avançados**: Por data, status, evento
- **Ordenação**: Por data, usuário, status

## 🔄 Sistema de Unificação

O sistema de unificação permite agrupar respostas similares para análise estatística.

### Como Funciona

1. **Respostas individuais** são armazenadas separadamente
2. **Administrador identifica** respostas similares
3. **Agrupa respostas** em categorias unificadas
4. **Gera estatísticas** baseadas nos grupos

### Processo de Unificação

1. **Acesse a página de unificação**:
   ```
   Votações > Resultados > [Votação] > Unificação
   ```

2. **Selecione respostas similares**
3. **Crie ou escolha um valor unificado**
4. **Confirme a unificação**

### Benefícios

- **Análise mais precisa** de dados qualitativos
- **Redução de ruído** em respostas abertas
- **Geração de insights** estatísticos
- **Relatórios mais limpos**

## 📁 Estrutura do Projeto
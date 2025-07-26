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
```
voting-system/
├── 📁 assets/              # Recursos estáticos
│   ├── 📁 css/             # Estilos CSS
│   │   ├── admin.css       # Estilos administrativos
│   │   ├── public.css      # Estilos públicos
│   │   ├── vs-user-votacoes.css  # 🆕 Estilos área do usuário
│   │   ├── vs-votacoes-feed.css  # Estilos feeds
│   │   └── vs-votacoes-home.css  # Estilos home
│   └── 📁 js/              # Scripts JavaScript
├── 📁 includes/            # Funcionalidades principais
│   ├── 📁 admin/           # Área administrativa
│   ├── 📁 ajax/            # Handlers AJAX
│   ├── 📁 core/            # Funcionalidades centrais
│   └── 📁 frontend/        # Frontend público
│       └── 📁 shortcodes/  # Shortcodes
│           ├── vs-shortcode-voting-form.php
│           ├── vs-shortcode-thank-you.php
│           ├── vs-shortcode-votacoes-feed.php
│           ├── vs-shortcode-votacoes-home-feed.php
│           └── vs-shortcode-user-votacoes.php  # 🆕 Área do usuário
├── 📁 templates/           # Templates de exibição
│   └── 📁 public/          # Templates públicos
│       ├── template-votacoes-usuario-ativas.php      # 🆕
│       ├── template-votacoes-usuario-encerradas.php  # 🆕
│       ├── template-votacoes-disponiveis.php         # 🆕
│       └── ...             # Outros templates
├── 📁 metaboxes/           # Metaboxes do WordPress
├── 📁 helpers/             # Funções utilitárias
├── 📁 languages/           # Arquivos de tradução
├── 📄 voting-system.php    # Arquivo principal
├── 📄 bootstrap.php        # Inicializador
└── 📄 README.md            # Este arquivo
```

## 👨‍💻 Desenvolvimento

### Configuração do Ambiente

1. **Clone o repositório**:
   ```bash
   git clone https://github.com/guilhermemandeli/voting-system.git
   cd voting-system
   ```

2. **Configure WordPress local**
3. **Ative o modo debug**:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

### Hooks Disponíveis

#### Actions
```php
// Após submissão de voto
do_action('vs_after_vote_submission', $vote_id, $user_id);

// Antes de atualizar voto
do_action('vs_before_vote_update', $vote_id, $user_id);

// Quando votação é encerrada
do_action('vs_voting_closed', $voting_id);
```

#### Filters
```php
// Modificar campos do formulário
$fields = apply_filters('vs_voting_form_fields', $fields, $voting_id);

// Modificar dados de exportação
$data = apply_filters('vs_export_data', $data, $voting_id);

// Modificar grupos de unificação
$groups = apply_filters('vs_unification_groups', $groups, $voting_id);
```

### Contribuindo

1. **Fork** o projeto
2. **Crie uma branch** para sua feature (`git checkout -b feature/AmazingFeature`)
3. **Commit** suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. **Push** para a branch (`git push origin feature/AmazingFeature`)
5. **Abra um Pull Request**

## ❓ FAQ

### Como posso personalizar os estilos?

Adicione CSS customizado no seu tema:

```css
/* Personalizar formulário de votação */
.vs-voting-form {
    /* Seus estilos aqui */
}

/* Personalizar cards do feed */
.vs-voting-card {
    /* Seus estilos aqui */
}

/* 🆕 Personalizar área do usuário */
.vs-votacao-card {
    /* Seus estilos aqui */
}
```

### Como criar uma página de usuário completa?

1. **Crie uma página** chamada "Minhas Votações" ou "/votacoes"
2. **Configure restrição** para usuários logados (usando plugin ou código)
3. **Adicione os shortcodes**:
   ```php
   <h2>Votações Ativas</h2>
   [votacoes_usuario_ativas]
   
   <h2>Votações Encerradas</h2>
   [votacoes_usuario_encerradas]
   
   <h2>Votações Disponíveis</h2>
   [votacoes_disponiveis]
   ```

### Como limitar quem pode votar?

Use o hook `vs_can_user_vote`:

```php
add_filter('vs_can_user_vote', function($can_vote, $user_id, $voting_id) {
    // Sua lógica de permissão aqui
    return $can_vote;
}, 10, 3);
```

### Como personalizar emails de notificação?

```php
add_filter('vs_notification_email', function($email_data, $voting_id) {
    // Personalizar dados do email
    return $email_data;
}, 10, 2);
```

### Como fazer backup dos dados?

1. **Exporte via CSV** na interface administrativa
2. **Backup do banco de dados** WordPress
3. **Backup dos arquivos** do plugin

## 🆘 Suporte

### Reportar Problemas

- **GitHub Issues**: [Criar nova issue](https://github.com/guilhermemandeli/voting-system/issues)
- **Documentação**: Consulte a documentação completa no arquivo `documentation.php`

### Logs de Debug

Para ativar logs detalhados:

```php
// No wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Acessar debug do plugin
/wp-admin/admin.php?debug_vs=1
```

### Problemas Comuns

#### Votação não aparece
- Verifique se o plugin está ativo
- Confirme se a votação está publicada
- Verifique permalinks

#### Erro 403 em AJAX
- Verifique nonces
- Confirme permissões de usuário
- Verifique logs de erro

#### Shortcodes da área do usuário não funcionam
- Confirme que o usuário está logado
- Verifique se há votações disponíveis
- Confirme que o CSS está carregando

## 📄 Licença

Este projeto está licenciado sob a **GPL v2 ou posterior** - veja o arquivo [LICENSE](LICENSE) para detalhes.

Copyright (C) 2025 Guilherme Mandeli

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

## 👨‍💻 Autor

**Guilherme Mandeli**
- GitHub: [@guilhermemandeli](https://github.com/guilhermemandeli)
- Website: [https://github.com/guilhermemandeli](https://github.com/guilhermemandeli)

---

⭐ **Se este projeto foi útil para você, considere dar uma estrela no GitHub!**

📧 **Dúvidas?** Abra uma [issue](https://github.com/guilhermemandeli/voting-system/issues) ou entre em contato!

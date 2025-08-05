# 🗳️ Voting System - Plugin WordPress

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-2.250731.1-orange.svg)](https://github.com/Guilherme-Mandeli/voting-system/releases)

Sistema completo de votações personalizado para WordPress com interface administrativa avançada, shortcodes flexíveis e sistema inteligente de unificação de respostas.

## 📋 Índice

- [Características](#-características)
- [Requisitos](#-requisitos)
- [Instalação](#-instalação)
- [Configuração](#-configuração)
- [Como Usar](#-como-usar)
- [Shortcodes](#-shortcodes)
- [Estrutura do Projeto](#-estrutura-do-projeto)
- [Funcionalidades Administrativas](#-funcionalidades-administrativas)
- [Sistema de Unificação](#-sistema-de-unificação)
- [Desenvolvimento](#-desenvolvimento)
- [FAQ](#-faq)
- [Suporte](#-suporte)
- [Licença](#-licença)

## ✨ Características

### 🎯 Funcionalidades Principais
- **Votações Personalizadas**: Crie votações com perguntas dinâmicas e diferentes tipos de campos
- **Interface Administrativa**: Painel completo para gerenciamento de votações e resultados
- **Sistema de Unificação**: Agrupe respostas similares
- **Shortcodes Flexíveis**: Integre votações em qualquer página ou post
- **Área do Usuário**: Painel personalizado para usuários gerenciarem suas votações
- **Feeds Públicos**: Exiba listas de votações com filtros avançados
- **Agendamento Automático**: Configure datas de início e fim das votações
- **Exportação CSV**: Exporte dados para análise externa
- **Responsivo**: Interface adaptada para desktop e mobile

### 🔧 Tipos de Campo Suportados
- ✅ Texto livre (input text)
- ✅ Área de texto (textarea)
- ✅ Seleção única (radio buttons)
- ✅ Seleção múltipla (checkboxes)
- ✅ Lista suspensa (select dropdown)

### 🛡️ Segurança
- Validação rigorosa de dados
- Proteção contra CSRF com nonces
- Sanitização de entrada e escape de saída
- Verificação de permissões de usuário
- Prevenção de acesso direto aos arquivos

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
   git clone https://github.com/Guilherme-Mandeli/voting-system.git
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
git clone https://github.com/Guilherme-Mandeli/voting-system.git /path/to/wordpress/wp-content/plugins/voting-system

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
[votacao_formulario id="123"]
```

#### Feed de Votações (Simples)
```php
[votacoes_feed]
```

#### Feed da Página Inicial (Avançado)
```php
[votacoes_home_feed]
```

#### Shortcode Genérico (Flexível)
```php
[votacoes_display layout="cards" group_by="year" show_filters="true"]
```

#### Página de Agradecimento
```php
[votacao_obrigado]
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

#### `[votacao_formulario]`
Exibe o formulário de uma votação específica.

**Parâmetros:**
- `id` (obrigatório): ID da votação

**Exemplo:**
```php
[votacao_formulario id="123"]
```

#### `[votacoes_display]` (Shortcode Genérico)
Shortcode flexível para exibir votações com múltiplas configurações.

**Parâmetros principais:**
- `limit`: Número máximo de votações (padrão: 10)
- `layout`: Tipo de layout - 'cards', 'list', 'grouped' (padrão: 'cards')
- `group_by`: Agrupar por - 'none', 'year', 'category', 'status' (padrão: 'none')
- `show_filters`: Mostrar filtros - true/false (padrão: false)
- `show_status`: Filtro por status - 'all', 'aberta', 'encerrada', 'em-pausa'
- `hide_encerradas`: Ocultar encerradas - true/false (padrão: false)
- `only_active`: Apenas ativas - true/false (padrão: false)

**Exemplos:**
```php
# Básico - exibe 10 votações em cards
[votacoes_display]

# Lista apenas votações ativas
[votacoes_display only_active="true" layout="list"]

# Cards agrupados por ano com filtros
[votacoes_display group_by="year" show_filters="true" limit="20"]

# Feed completo com todas as funcionalidades
[votacoes_display limit="50" group_by="year" show_filters="true" show_time_remaining="true"]
```

#### `[votacoes_home_feed]`
Feed principal com cards visuais agrupados.

**Parâmetros:**
- `limit`: Número de votações (padrão: 20)
- `show_status`: Filtrar por status
- `hide_encerradas`: Ocultar encerradas
- `show_filters`: Exibir filtros (padrão: true)

**Exemplo:**
```php
[votacoes_home_feed limit="30" hide_encerradas="true"]
```

#### `[votacoes_feed]`
Feed simples de votações.

**Parâmetros:**
- `ano`: Filtrar por ano
- `status`: Filtrar por status
- `evento`: Filtrar por evento
- `posts_per_page`: Número de itens por página

**Exemplo:**
```php
[votacoes_feed ano="2024" status="aberta" posts_per_page="10"]
```

### Shortcodes da Área do Usuário

#### `[votacoes_usuario_ativas]`
Lista votações que o usuário respondeu e ainda podem ser editadas.

**Características:**
- ✅ Restrição para usuários logados
- ✅ Filtra votações não encerradas
- ✅ Exibe resumo das respostas
- ✅ Botão "Editar Voto" quando permitido
- ✅ Botão "Ver Respostas" padrão

#### `[votacoes_usuario_encerradas]`
Lista votações que o usuário participou e já estão encerradas.

**Características:**
- ✅ Restrição para usuários logados
- ✅ Filtra votações encerradas
- ✅ Exibe status "Encerrada"
- ✅ Resumo das respostas do usuário
- ✅ Botão "Ver Respostas"

#### `[votacoes_disponiveis]`
Lista votações em aberto que o usuário ainda não participou.

**Características:**
- ✅ Restrição para usuários logados
- ✅ Filtra votações não respondidas
- ✅ Exibe prazo de encerramento
- ✅ Botão "Participar" destacado
- ✅ Descrição da votação

#### `[votacao_obrigado]`
Página de agradecimento pós-votação.

**Características:**
- ✅ Validação por token temporário
- ✅ Exibe resumo das respostas
- ✅ Informações da votação
- ✅ Links para outras ações

## 🏗️ Estrutura do Projeto
```
voting-system/
├── voting-system.php          # Arquivo principal do plugin
├── bootstrap.php              # Carregamento e inicialização
├── documentation.php          # Documentação técnica
├── documentation-shortcodes.php # Documentação de shortcodes
├── README.md                  # Este arquivo
├── LICENSE                    # Licença GPL v2
│
├── assets/                    # Recursos estáticos
│   ├── css/                   # Arquivos de estilo
│   │   ├── admin.css         # Estilos administrativos
│   │   ├── public.css        # Estilos públicos
│   │   └── public/           # Estilos específicos
│   └── js/                   # Scripts JavaScript
│       ├── admin.js          # Scripts administrativos
│       ├── public.js         # Scripts públicos
│       └── ajax/             # Handlers AJAX
│
├── includes/                  # Funcionalidades principais
│   ├── core/                 # Núcleo do sistema
│   │   ├── cpt/              # Custom Post Types
│   │   ├── tax/              # Taxonomias
│   │   ├── submission/       # Processamento de formulários
│   │   └── cron/             # Agendamento automático
│   │
│   ├── admin/                # Interface administrativa
│   │   ├── pages/            # Páginas administrativas
│   │   ├── menus/            # Menus e submenus
│   │   └── data/             # Exportação/Importação
│   │
│   ├── frontend/             # Interface pública
│   │   └── shortcodes/       # Shortcodes
│   │
│   └── ajax/                 # Handlers AJAX
│       ├── get-user-votes.php
│       ├── vs-handle-get-unificacao-group.php
│       └── vs-handle-update-unificacao.php
│
├── helpers/                   # Funções utilitárias
│   ├── vs-utils-data.php     # Manipulação de dados
│   ├── vs-utils-css-loader.php # Carregamento de CSS
│   ├── vs-utils-permissions.php # Controle de permissões
│   ├── vs-utils-templates.php # Helpers de templates
│   └── vs-utils-votacoes.php # Funções de votações
│
├── metaboxes/                # Interface de edição
│   ├── vs-metabox-voting-info.php # Informações da votação
│   ├── vs-metabox-questions.php # Gerenciamento de perguntas
│   ├── vs-metabox-answer-details.php # Detalhes de resposta
│   └── view-metabox-questions.php # Visualização de perguntas
│
├── templates/                # Templates
│   ├── admin/                # Templates administrativos
│   │   ├── template-metabox-voting-info.php
│   │   ├── template-results-list.php
│   │   └── template-results-unificacao.php
│   │
│   └── public/               # Templates públicos
│       ├── template-voting-form.php
│       ├── template-thank-you.php
│       ├── template-votacoes-feed.php
│       ├── template-home-feed.php
│       ├── template-votacoes-usuario-ativas.php
│       ├── template-votacoes-usuario-encerradas.php
│       ├── template-votacoes-disponiveis.php
│       └── template-votacoes-generic-*.php
│
└── languages/                # Internacionalização
└── voting-system.pot     # Arquivo de tradução
```

## 🔧 Funcionalidades Administrativas

### Painel de Controle
- **Dashboard**: Visão geral das votações
- **Gerenciamento**: Criar, editar e excluir votações
- **Resultados**: Visualizar respostas e ranking
- **Unificação**: Agrupar respostas similares
- **Exportação**: Download de dados em CSV

### Metaboxes de Edição
- **Informações da Votação**: Status, datas, código
- **Perguntas**: Adicionar e configurar perguntas
- **Detalhes de Resposta**: Visualizar respostas individuais

### Páginas Administrativas
- **Lista de Votações**: Visão geral com filtros
- **Detalhes de Resultados**: Análise aprofundada
- **Unificação de Respostas**: Interface para agrupamento

## 🔄 Sistema de Unificação

### Como Funciona
1. **Coleta**: Respostas são armazenadas individualmente
2. **Análise**: Administrador identifica respostas similares
3. **Agrupamento**: Seleção de respostas para unificar
4. **Unificação**: Criação de resposta unificada
5. **Aplicação**: Atualização automática via AJAX

### Benefícios
- **Ranking**: Dados facilitados
- **Relatórios Limpos**: Menos variações desnecessárias
- **Insights Melhores**: Padrões mais claros
- **Exportação Organizada**: CSV com dados unificados

### Interface
- **Seleção Visual**: Checkboxes para escolher respostas
- **Modal de Unificação**: Interface intuitiva
- **Contadores**: Visualização de agrupamentos
- **Histórico**: Rastreamento de alterações

## 🛠️ Desenvolvimento

### Estrutura de Hooks

#### Actions Disponíveis
```php
// Antes de submeter voto
do_action('vs_before_vote_submit', $vote_data, $user_id);

// Após submeter voto
do_action('vs_after_vote_submit', $vote_id, $user_id);

// Antes de atualizar voto
do_action('vs_before_vote_update', $vote_id, $user_id);

// Após atualizar voto
do_action('vs_after_vote_update', $vote_id, $user_id);
```

#### Filters Disponíveis
```php
// Modificar argumentos da query
$args = apply_filters('vs_generic_query_args', $args, $params);

// Modificar dados das votações
$votacoes = apply_filters('vs_generic_votacoes_data', $votacoes, $options);

// Modificar dados do template
$data = apply_filters('vs_generic_template_data', $data, $context);

// Modificar dados de exportação
$export_data = apply_filters('vs_export_csv_data', $data, $votacao_id);
```

### Funções Utilitárias

#### Principais Helpers
```php
// Recuperar perguntas da votação
$questions = vs_get_voting_questions($votacao_id);

// Formatar resposta para exibição
$resposta_formatada = vs_format_answer($resposta);

// Verificar se usuário já votou
$existing = vs_get_existing_response($user_id, $votacao_id);

// Atualizar metadados de resposta
vs_update_response_metadata($post_id, $votacao_id, $user_id, $respostas);

// Verificar status da votação
$is_encerrada = vs_check_votacao_status($data_fim);
```

### Customização de Templates

#### Sobrescrever Templates
1. Copie o template de `templates/public/` para seu tema
2. Mantenha a estrutura de pastas: `seu-tema/voting-system/`
3. Modifique conforme necessário

#### Exemplo de Customização
```php
// No seu tema: voting-system/template-voting-form.php
<?php
// Sua customização do formulário de votação
?>
```

## ❓ FAQ

### Como criar uma votação?
1. Vá para `Votações > Adicionar Nova`
2. Preencha título e descrição
3. Adicione perguntas no metabox
4. Configure status e datas
5. Publique

### Como exibir votações na página inicial?
Use o shortcode `[votacoes_home_feed]` ou `[votacoes_display]` com os parâmetros desejados.

### Como permitir edição de votos?
Marque a opção "Permitir edição" no metabox da votação.

### Como exportar dados?
Na página de resultados, clique no link "Exportar CSV".

### Como unificar respostas?
1. Vá para a página de unificação da votação
2. Selecione respostas similares
3. Crie ou escolha uma resposta unificada
4. Aplique a unificação

### Como criar área do usuário?
1. Crie uma página (ex: `/votacoes`)
2. Adicione os shortcodes de usuário
3. Configure restrição para usuários logados

## 📞 Suporte

### Documentação
- **Técnica**: `documentation.php`
- **Shortcodes**: `documentation-shortcodes.php`
- **Usuário**: Este README

### Contato
- **GitHub**: [Issues](https://github.com/Guilherme-Mandeli/voting-system/issues)
- **Email**: [Suporte](mailto:guil.mandeli@gmail.com)

### Contribuição
1. Fork o projeto
2. Crie uma branch para sua feature
3. Commit suas mudanças
4. Push para a branch
5. Abra um Pull Request

## 📄 Licença

Este projeto está licenciado sob a GPL v2 ou posterior - veja o arquivo [LICENSE](LICENSE) para detalhes.

---

**Desenvolvido com ❤️ por [Guilherme Mandeli](https://github.com/Guilherme-Mandeli)**

<?php
/**
 * ===============================================================================
 * DOCUMENTAÇÃO DO SISTEMA DE VOTAÇÕES - VOTING SYSTEM v2.250729.3
 * ===============================================================================
 * 
 * Plugin WordPress para criação e gerenciamento de votações personalizadas
 * Desenvolvido por: Guilherme Mandeli
 * Versão: 2.250730.1
 * 
 */

defined('ABSPATH') || exit;
/**
 * ===============================================================================
 * SEÇÃO 1: DOCUMENTAÇÃO PARA DESENVOLVEDORES
 * ===============================================================================
 */

/**
 * VISÃO GERAL DO PROJETO
 * =====================
 * 
 * O Voting System é um plugin WordPress completo para criação e gerenciamento
 * de votações personalizadas. O sistema permite:
 * 
 * - Criação de votações com perguntas dinâmicas
 * - Diferentes tipos de campos (texto, select, radio, checkbox)
 * - Sistema de unificação de respostas para análise
 * - Interface administrativa completa
 * - Feeds públicos para exibição das votações
 * - Sistema de agendamento automático
 * - Exportação de dados em CSV
 * 
 * ARQUITETURA DO SISTEMA
 * =====================
 * 
 * O plugin segue uma arquitetura modular organizada em:
 * 
 * 1. CORE (includes/core/):
 *    - CPTs: Custom Post Types (votacoes, votacao_resposta)
 *    - Taxonomias: Eventos para categorização
 *    - Submission: Processamento de formulários
 *    - Cron: Agendamento automático de tarefas
 * 
 * 2. FRONTEND (includes/frontend/):
 *    - Shortcodes para exibição pública
 *    - Templates para renderização
 * 
 * 3. ADMIN (includes/admin/):
 *    - Páginas administrativas
 *    - Menus e submenus
 *    - Exportação/Importação de dados
 * 
 * 4. AJAX (includes/ajax/):
 *    - Handlers para requisições assíncronas
 *    - Funcionalidades de unificação
 *    - Carregamento dinâmico de dados
 * 
 * 5. HELPERS (helpers/):
 *    - Funções utilitárias
 *    - Processamento de dados
 *    - Templates e permissões
 * 
 * 6. METABOXES (metaboxes/):
 *    - Interface de edição de votações
 *    - Gerenciamento de perguntas
 *    - Visualização de respostas
 * 
 * CUSTOM POST TYPES
 * ================
 * 
 * 1. 'votacoes':
 *    - Post principal que representa uma votação
 *    - Meta fields: vs_perguntas, _vs_ano, _vs_status, _vs_codigo, etc.
 *    - Suporta: title, editor, excerpt
 *    - Taxonomia: eventos
 * 
 * 2. 'votacao_resposta':
 *    - Armazena respostas individuais dos usuários
 *    - Meta fields: vs_votacao_id, vs_usuario_id, vs_respostas_detalhadas, vs_resposta_unificada
 *    - Privado, apenas para administração
 * 
 * SISTEMA DE METADADOS
 * ===================
 * 
 * Votação (votacoes):
 * - vs_perguntas: Array com as perguntas da votação
 * - _vs_ano: Ano da votação
 * - _vs_status: aberta|em-pausa|encerrada
 * - _vs_codigo: Código único da votação
 * - _vs_data_inicio: Data de início
 * - _vs_data_fim: Data de encerramento
 * - _vs_qtd_votos: Quantidade de votos
 * - _vs_votantes: Array com IDs dos usuários que votaram
 * - _vs_permitir_edicao: Permite edição de votos
 * 
 * Resposta (votacao_resposta):
 * - vs_votacao_id: ID da votação relacionada
 * - vs_usuario_id: ID do usuário que respondeu
 * - vs_respostas_detalhadas: Array com respostas por pergunta
 * - vs_resposta_unificada: Array com respostas unificadas por índice
 * - vs_data_envio: Data/hora do envio
 * 
 * SHORTCODES DISPONÍVEIS
 * =====================
 * 
 * SHORTCODES PRINCIPAIS:
 * 
 * 1. [vs_voting_form id="123"]
 *    - Exibe formulário de votação
 *    - Parâmetros: id (obrigatório)
 *    - Funcionalidades: validação, edição, submissão
 * 
 * 2. [vs_thank_you]
 *    - Página de agradecimento pós-votação
 *    - Exibe resumo das respostas
 *    - Validação por token temporário
 * 
 * 3. [vs_votacoes_feed]
 *    - Feed filtrado de votações
 *    - Filtros: ano, status, código, evento
 *    - Paginação automática
 * 
 * 4. [vs_votacoes_home_feed]
 *    - Feed principal com cards visuais
 *    - Agrupamento por ano e evento
 *    - Interface responsiva
 * 
 * SHORTCODES DA ÁREA DO USUÁRIO (NOVOS):
 * 
 * 5. [votacoes_usuario_ativas]
 *    - Lista votações que o usuário respondeu e ainda podem ser editadas
 *    - Restrição: apenas usuários logados
 *    - Exibe: título, data, resumo das respostas, botão "Editar Voto"
 *    - Filtro: apenas votações com status "aberta" ou não encerradas por data
 *    - Template: template-votacoes-usuario-ativas.php
 * 
 * 6. [votacoes_usuario_encerradas]
 *    - Lista votações que o usuário participou e já estão encerradas
 *    - Restrição: apenas usuários logados
 *    - Exibe: título, status "Encerrada", resumo das respostas, botão "Ver Respostas"
 *    - Filtro: votações com status "encerrada" ou encerradas por data
 *    - Template: template-votacoes-usuario-encerradas.php
 * 
 * 7. [votacoes_disponiveis]
 *    - Lista votações em aberto que o usuário ainda não participou
 *    - Restrição: apenas usuários logados
 *    - Exibe: título, prazo, descrição, botão "Participar"
 *    - Filtro: votações abertas onde o usuário ainda não votou
 *    - Template: template-votacoes-disponiveis.php
 * 
 * FUNCIONALIDADES DOS SHORTCODES DA ÁREA DO USUÁRIO:
 * 
 * - Verificação automática de login (exibe mensagem se não logado)
 * - Uso de get_current_user_id() para identificar usuário
 * - Consultas otimizadas com WP_Query e meta_query
 * - HTML puro compatível com Divi Builder
 * - CSS responsivo integrado (vs-user-votacoes.css)
 * - Função helper vs_generate_response_summary() para resumos
 * - Verificação de status das votações (ativa/encerrada)
 * - Templates separados para melhor organização
 * 
 * SISTEMA DE UNIFICAÇÃO
 * ====================
 * 
 * O sistema de unificação permite agrupar respostas similares para análise:
 * 
 * FUNCIONAMENTO:
 * 1. Respostas são armazenadas individualmente em vs_respostas_detalhadas
 * 2. Administrador pode unificar respostas similares via interface
 * 3. Unificações são salvas em vs_resposta_unificada como array indexado
 * 4. Cada índice corresponde a uma pergunta específica
 * 
 * PROCESSO DE UNIFICAÇÃO:
 * 1. Seleção de respostas na interface administrativa
 * 2. Criação de novo valor unificado OU seleção de existente
 * 3. AJAX atualiza vs_resposta_unificada para as respostas selecionadas
 * 4. Interface mostra contadores e agrupamentos
 * 
 * ESTRUTURA DE DADOS:
 * vs_resposta_unificada = [
 *     0 => "Resposta Unificada Pergunta 1",
 *     1 => "Resposta Unificada Pergunta 2",
 *     // índice = posição da pergunta
 * ]
 * 
 * FLUXO DE TRABALHO
 * ================
 * 
 * 1. CRIAÇÃO DE VOTAÇÃO:
 *    - Admin cria post tipo 'votacoes'
 *    - Define perguntas via metabox
 *    - Configura datas e status
 *    - Publica votação
 * 
 * 2. PARTICIPAÇÃO DO USUÁRIO:
 *    - Usuário acessa via shortcode
 *    - Preenche formulário dinâmico
 *    - Sistema valida e salva resposta
 *    - Redirecionamento para página de agradecimento
 * 
 * 3. GERENCIAMENTO PELO USUÁRIO (NOVO):
 *    - Usuário acessa página /votacoes (ou similar)
 *    - Visualiza votações ativas, encerradas e disponíveis
 *    - Pode editar votações ativas
 *    - Pode participar de novas votações
 *    - Visualiza histórico completo
 * 
 * 4. ANÁLISE DE RESULTADOS:
 *    - Admin acessa página de resultados
 *    - Visualiza respostas individuais
 *    - Realiza unificação de respostas
 *    - Exporta dados em CSV
 * 
 * SEGURANÇA
 * =========
 * 
 * - Nonces em todas as operações AJAX
 * - Sanitização de dados de entrada
 * - Verificação de capabilities
 * - Validação de tokens temporários
 * - Escape de saída de dados
 * - Verificação de login nos shortcodes da área do usuário
 * 
 * PERFORMANCE
 * ===========
 * 
 * - Carregamento condicional de assets
 * - Cache de consultas pesadas
 * - Paginação em listagens
 * - Otimização de queries
 * - CSS específico para área do usuário carregado apenas quando necessário
 * 
 * HOOKS E FILTROS
 * ==============
 * 
 * Actions:
 * - vs_after_vote_submission: Após submissão de voto
 * - vs_before_vote_update: Antes de atualizar voto
 * - vs_voting_closed: Quando votação é encerrada
 * 
 * Filters:
 * - vs_voting_form_fields: Modifica campos do formulário
 * - vs_export_data: Modifica dados de exportação
 * - vs_unification_groups: Modifica grupos de unificação
 */

/**
 * ===============================================================================
 * SEÇÃO 2: CONTEXTO PARA IA - ASSISTENTE DE DESENVOLVIMENTO
 * ===============================================================================
 */

/**
 * CONTEXTO PARA ASSISTENTE IA
 * ===========================
 * 
 * Este projeto é um sistema completo de votações para WordPress. Aqui estão
 * as informações essenciais para continuar o desenvolvimento:
 * 
 * ESTRUTURA DE ARQUIVOS PRINCIPAIS:
 * 
 * 1. BOOTSTRAP (bootstrap.php):
 *    - Ponto de entrada principal
 *    - Carrega todos os componentes
 *    - Gerencia assets CSS/JS
 *    - Classe VS_Bootstrap controla inicialização
 * 
 * 2. CUSTOM POST TYPES:
 *    - includes/core/cpt/vs-register-cpt-voting.php (votacoes)
 *    - includes/core/cpt/vs-register-cpt-answer.php (votacao_resposta)
 * 
 * 3. SHORTCODES IMPLEMENTADOS:
 *    - vs_voting_form: Formulário de votação principal
 *    - vs_thank_you: Página de agradecimento
 *    - vs_votacoes_feed: Feed filtrado de votações
 *    - vs_votacoes_home_feed: Feed principal com cards
 *    - votacoes_usuario_ativas: Votações ativas do usuário (NOVO)
 *    - votacoes_usuario_encerradas: Votações encerradas do usuário (NOVO)
 *    - votacoes_disponiveis: Votações disponíveis para o usuário (NOVO)
 * 
 * 4. SISTEMA AJAX:
 *    - get-user-votes.php: Carrega respostas de usuário
 *    - vs-handle-get-unificacao-group.php: Gerencia grupos de unificação
 *    - vs-handle-update-unificacao.php: Atualiza unificações
 * 
 * OBJETOS E ESTRUTURAS DE DADOS IMPORTANTES:
 * 
 * 1. PERGUNTA (vs_perguntas):
 * [
 *     'label' => 'Texto da pergunta',
 *     'type' => 'text|select|radio|checkbox',
 *     'options' => ['opcao1', 'opcao2'], // para select/radio/checkbox
 *     'required' => true|false
 * ]
 * 
 * 2. RESPOSTA DETALHADA (vs_respostas_detalhadas):
 * [
 *     0 => 'Resposta pergunta 1',
 *     1 => ['opcao1', 'opcao2'], // para checkbox
 *     2 => 'Resposta pergunta 3'
 * ]
 * 
 * 3. RESPOSTA UNIFICADA (vs_resposta_unificada):
 * [
 *     0 => 'Grupo A',
 *     1 => 'Categoria X',
 *     2 => 'Tipo Y'
 * ]
 * 
 * SISTEMA DE UNIFICAÇÃO - DETALHES TÉCNICOS:
 * 
 * A unificação funciona em duas etapas:
 * 
 * 1. COLETA DE DADOS:
 *    - Interface mostra duas colunas
 *    - Coluna 1: Respostas individuais com checkboxes
 *    - Coluna 2: Grupos unificados com contadores
 * 
 * 2. PROCESSO DE UNIFICAÇÃO:
 *    - Usuário seleciona respostas similares
 *    - Pode criar novo grupo OU usar existente
 *    - AJAX envia dados para vs-handle-update-unificacao.php
 *    - Sistema atualiza meta vs_resposta_unificada
 * 
 * FUNÇÕES HELPER IMPORTANTES:
 * 
 * - vs_get_voting_questions($votacao_id): Obtém perguntas
 * - vs_format_unified_answer($value): Formata resposta unificada
 * - vs_update_resposta_unificada_indices($id, $indices, $value): Atualiza unificação
 * - vs_get_existing_response($user_id, $votacao_id): Verifica resposta existente
 * - vs_generate_response_summary($respostas, $perguntas): Gera resumo de respostas (NOVA)
 * - vs_user_already_voted($post_id, $user_id): Verifica se usuário já votou
 * - vs_check_votacao_status($data_fim): Verifica se votação está encerrada
 * 
 * TEMPLATES PRINCIPAIS:
 * 
 * - template-voting-form.php: Formulário principal
 * - template-voting-form-fields.php: Campos dinâmicos
 * - template-results-unificacao.php: Interface de unificação
 * - template-home-feed.php: Feed principal
 * - template-votacoes-usuario-ativas.php: Votações ativas do usuário (NOVO)
 * - template-votacoes-usuario-encerradas.php: Votações encerradas do usuário (NOVO)
 * - template-votacoes-disponiveis.php: Votações disponíveis (NOVO)
 * 
 * ASSETS CSS/JS:
 * 
 * - vs-votacoes-feed.css: Estilos para feeds
 * - vs-votacoes-home.css: Estilos para home
 * - vs-user-votacoes.css: Estilos para área do usuário (NOVO)
 * - votacao-ajax.js: Modal de respostas
 * - vs-handle-get-unificacao-group.js: Interface de unificação
 * 
 * PÁGINAS ADMINISTRATIVAS:
 * 
 * - vs-page-results-list.php: Lista de resultados
 * - vs-page-results-details.php: Detalhes com usuários
 * - vs-page-results-unificacao.php: Interface de unificação
 * 
 * METABOXES:
 * 
 * - vs-metabox-voting-info.php: Informações da votação
 * - vs-metabox-questions.php: Gerenciamento de perguntas
 * - vs-metabox-answer-details.php: Detalhes de resposta
 * 
 * SISTEMA CRON:
 * 
 * - vs-voting-scheduler.php: Encerramento automático
 * - Executa diariamente às 00:00
 * - Atualiza status para 'encerrada' baseado em data_fim
 * 
 * ÁREA DO USUÁRIO - DETALHES TÉCNICOS (NOVO):
 * 
 * ARQUIVOS CRIADOS:
 * - includes/frontend/shortcodes/vs-shortcode-user-votacoes.php
 * - templates/public/template-votacoes-usuario-ativas.php
 * - templates/public/template-votacoes-usuario-encerradas.php
 * - templates/public/template-votacoes-disponiveis.php
 * - assets/css/vs-user-votacoes.css
 * 
 * FUNCIONALIDADES:
 * 1. Verificação automática de login
 * 2. Consultas otimizadas por usuário
 * 3. Filtros por status de votação
 * 4. Interface responsiva com cards
 * 5. Resumo inteligente de respostas
 * 6. Botões contextuais (Editar/Ver/Participar)
 * 
 * LÓGICA DE FILTROS:
 * - Ativas: status != 'encerrada' AND data_fim não passou
 * - Encerradas: status == 'encerrada' OR data_fim passou
 * - Disponíveis: status == 'aberta' AND usuário não votou AND não encerrada
 * 
 * FLUXO DE UNIFICAÇÃO DETALHADO:
 * 
 * 1. Admin acessa página de unificação
 * 2. Sistema carrega respostas via AJAX
 * 3. Interface mostra duas colunas:
 *    - Esquerda: Respostas individuais
 *    - Direita: Grupos unificados
 * 4. Admin seleciona respostas similares
 * 5. Cria novo grupo OU seleciona existente
 * 6. AJAX atualiza banco de dados
 * 7. Interface recarrega com novos agrupamentos
 * 
 * CONSIDERAÇÕES PARA DESENVOLVIMENTO:
 * 
 * - Sempre usar nonces para segurança
 * - Sanitizar dados de entrada
 * - Escapar dados de saída
 * - Verificar capabilities do usuário
 * - Usar transients para cache quando necessário
 * - Manter compatibilidade com versões anteriores
 * - Documentar mudanças significativas
 * - Testar responsividade em dispositivos móveis
 * - Verificar performance com muitos dados
 * 
 * PRÓXIMOS DESENVOLVIMENTOS SUGERIDOS:
 * 
 * - Sistema de notificações por email
 * - Relatórios avançados com gráficos
 * - API REST para integração externa
 * - Sistema de templates personalizáveis
 * - Backup automático de dados
 * - Logs de auditoria
 * - Integração com outros plugins
 * - Dashboard personalizado para usuários
 * - Sistema de favoritos/bookmarks
 * - Filtros avançados na área do usuário
 * 
 * DEBUGGING E TROUBLESHOOTING:
 * 
 * - Ativar WP_DEBUG para desenvolvimento
 * - Verificar logs do WordPress
 * - Usar browser dev tools para AJAX
 * - Verificar permissões de arquivo
 * - Confirmar estrutura de banco de dados
 * - Testar com diferentes temas
 * - Verificar compatibilidade com Divi
 * 
 * TESTES RECOMENDADOS:
 * 
 * - Criação e edição de votações
 * - Submissão de respostas
 * - Processo de unificação
 * - Exportação de dados
 * - Funcionalidade de feeds
 * - Área do usuário (novos shortcodes)
 * - Responsividade mobile
 * - Performance com muitos dados
 * - Compatibilidade com diferentes temas
 * - Funcionalidade com usuários não logados
 */

// Fim da documentação
?>
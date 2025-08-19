<?php
/**
 * ===============================================================================
 * DOCUMENTAÇÃO DO SISTEMA DE VOTAÇÕES - VOTING SYSTEM v2.250731.1
 * ===============================================================================
 * 
 * Plugin WordPress para criação e gerenciamento de votações personalizadas
 * Desenvolvido por: Guilherme Mandeli
 * Versão: 2.250731.1
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
 * - Diferentes tipos de campos (texto, select, radio, checkbox, imported_vote)
 * - Sistema de unificação de respostas para análise
 * - Interface administrativa completa
 * - Feeds públicos para exibição das votações
 * - Sistema de agendamento automático
 * - Exportação de dados em CSV
 * - Área do usuário para gerenciamento de participações
 * - Sistema de importação de respostas de votações anteriores
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
 *    - Meta fields: vs_questions, _vs_ano, _vs_status, _vs_codigo, etc.
 *    - Suporta: title, editor, excerpt
 *    - Taxonomia: eventos
 * 
 * 2. 'votacao_resposta':
 *    - Armazena respostas individuais dos usuários
 *    - Meta fields: vs_votacao_id, vs_usuario_id, vs_respostas_detalhadas, vs_resposta_unificada
 *    - Privado, apenas para administração
 * 
 * TAXONOMIA
 * =========
 * 
 * 'eventos':
 *    - Taxonomia hierárquica para categorizar votações
 *    - Permite agrupar votações por evento/categoria
 *    - Suporte à API REST e interface administrativa
 *    - Slug reescrito para 'eventos'
 * 
 * ===============================================================================
 * SEÇÃO 2: ESTRUTURAS DE DADOS JSON E OBJETOS IMPORTANTES
 * ===============================================================================
 * 
 * ESTRUTURA DE PERGUNTAS (vs_questions)
 * =====================================
 * 
 * Localização: Metadado do CPT 'votacoes'
 * Função de processamento: vs_format_user_answers() em vs-utils-data.php
 * 
 * Estrutura JSON completa:
 * {
 *   "0": {
 *     "label": "Qual sua opinião sobre o projeto?",
 *     "tipo": "radio",
 *     "options": [
 *       "Aprovo totalmente",
 *       "Aprovo parcialmente", 
 *       "Reprovo"
 *     ],
 *     "obrigatoria": true,
 *     "unificada": "",
 *     "imported_vote_id": null,
 *     "imported_answers": "{\"perguntas\": []}",
 *     "valores_reais": ["Aprovo totalmente", "Aprovo parcialmente", "Reprovo"],
 *     "question_source": ""
 *   },
 *   "1": {
 *     "label": "Comentários adicionais:",
 *     "tipo": "textarea",
 *     "options": [""],
 *     "obrigatoria": false,
 *     "unificada": "",
 *     "imported_vote_id": null,
 *     "imported_answers": "{\"perguntas\": []}"
 *   },
 *   "2": {
 *     "label": "Avaliação baseada em votação anterior",
 *     "tipo": "imported_vote",
 *     "options": ["Excelente", "Bom", "Regular", "Ruim"],
 *     "obrigatoria": true,
 *     "unificada": "",
 *     "imported_vote_id": 456,
 *     "imported_answers": "{\"perguntas\": [{\"question_source\": \"Como avalia o evento?\", \"question_index\": 1, \"imported_answers\": [{\"value\": \"Muito bom evento\", \"value_unificada\": \"Positivo\", \"qtd_votos\": 15}]}]}",
 *     "valores_reais": ["Muito bom evento", "Evento regular", "Evento fraco", "Evento ruim"],
 *     "question_source": "Como avalia o evento?"
 *   }
 * }
 * 
 * CAMPOS DA ESTRUTURA DE PERGUNTAS:
 * 
 * - label (string): Texto da pergunta exibido ao usuário
 * - tipo (string): Tipo do campo com valores possíveis:
 *   * "texto": Campo de texto simples
 *   * "textarea": Área de texto multilinha
 *   * "radio": Seleção única (radio buttons)
 *   * "checkbox": Seleção múltipla
 *   * "select": Lista suspensa
 *   * "imported_vote": Pergunta importada de votação anterior
 * - options (array): Lista de opções para campos de seleção
 * - obrigatoria (boolean): Define se a pergunta é obrigatória
 * - unificada (string): Valor para unificação de respostas
 * - imported_vote_id (int|null): ID da votação de origem (para perguntas importadas)
 * - imported_answers (string JSON): Dados das respostas importadas (ver estrutura abaixo)
 * - valores_reais (array): Valores originais das respostas (usado em imported_vote)
 * - question_source (string): Texto original da pergunta importada
 * 
 * ESTRUTURA DE RESPOSTAS DETALHADAS (vs_respostas_detalhadas)
 * ==========================================================
 * 
 * Localização: Metadado do CPT 'votacao_resposta'
 * Função de processamento: vs_update_response_metadata() em vs-utils-data.php
 * 
 * Estrutura JSON:
 * {
 *   "0": "Aprovo totalmente",
 *   "1": "O projeto está bem estruturado e atende às necessidades da comunidade.",
 *   "2": ["Opção A", "Opção C"],
 *   "3": "Resposta de texto livre detalhada",
 *   "4": "Muito bom evento"
 * }
 * 
 * DETALHES DA ESTRUTURA:
 * - Chave: Índice numérico correspondente à ordem da pergunta (0, 1, 2, ...)
 * - Valor: Resposta do usuário
 *   * String simples: para campos texto, textarea, radio, select
 *   * Array de strings: para campos checkbox (seleção múltipla)
 *   * String: para campos imported_vote (valor original selecionado)
 * 
 * ESTRUTURA DE RESPOSTAS UNIFICADAS (vs_resposta_unificada)
 * ========================================================
 * 
 * Localização: Metadado do CPT 'votacao_resposta'
 * Função de processamento: vs_update_resposta_unificada_indices() em vs-utils-data.php
 * 
 * Estrutura JSON:
 * {
 *   "0": "Aprovação Total",
 *   "1": "Comentário Positivo",
 *   "2": "Múltiplas Seleções",
 *   "3": "Feedback Detalhado",
 *   "4": "Avaliação Positiva"
 * }
 * 
 * PROPÓSITO E FUNCIONAMENTO:
 * - Permite agrupar respostas similares para análise estatística
 * - Mantém as respostas originais intactas em vs_respostas_detalhadas
 * - Cada índice corresponde exatamente ao índice da pergunta
 * - Administradores podem unificar manualmente via interface
 * - Usado para gerar relatórios e estatísticas agrupadas
 * 
 * ESTRUTURA DE RESPOSTAS IMPORTADAS (imported_answers)
 * ===================================================
 * 
 * Localização: Campo JSON nas perguntas do tipo 'imported_vote'
 * Função de processamento: vs_get_imported_vote_data() em vs-utils-data.php
 * 
 * Estrutura JSON completa:
 * {
 *   "perguntas": [
 *     {
 *       "question_source": "Como você avalia o evento anterior?",
 *       "question_index": 1,
 *       "vote_id": 456,
 *       "total_votos": 26,
 *       "imported_answers": [
 *         {
 *           "value": "Excelente evento, muito bem organizado e informativo",
 *           "value_unificada": "Avaliação Muito Positiva",
 *           "qtd_votos": 15
 *         },
 *         {
 *           "value": "Bom evento, mas alguns aspectos podem melhorar",
 *           "value_unificada": "Avaliação Positiva com Ressalvas", 
 *           "qtd_votos": 8
 *         },
 *         {
 *           "value": "Evento regular, atendeu expectativas básicas",
 *           "value_unificada": "Avaliação Neutra",
 *           "qtd_votos": 2
 *         },
 *         {
 *           "value": "Evento mal organizado, não recomendo",
 *           "value_unificada": "Avaliação Negativa",
 *           "qtd_votos": 1
 *         }
 *       ]
 *     }
 *   ],
 *   "manual_items": [
 *     {
 *       "text": "Opção criada manualmente",
 *       "vs_valor_real": "Opção criada manualmente"
 *     },
 *     {
 *       "text": "Outra opção manual",
 *       "vs_valor_real": "Outra opção manual"
 *     }
 *   ],
 *   "imported_items": [
 *     {
 *       "text": "Excelente evento, muito bem organizado e informativo",
 *       "vs_valor_real": "Excelente evento, muito bem organizado e informativo"
 *     },
 *     {
 *       "text": "Bom evento, mas alguns aspectos podem melhorar",
 *       "vs_valor_real": "Bom evento, mas alguns aspectos podem melhorar"
 *     }
 *   ]
 * }
 * 
 * CAMPOS DA ESTRUTURA DE RESPOSTAS IMPORTADAS:
 * - perguntas (array): Lista de perguntas importadas
 *   * question_source (string): Texto original da pergunta na votação anterior
 *   * question_index (int): Índice da pergunta na votação original (1-based)
 *   * vote_id (int): ID da votação de origem
 *   * total_votos (int): Total de respostas para esta pergunta
 *   * imported_answers (array): Lista de respostas com estatísticas
 *     - value (string): Resposta original exata do usuário
 *     - value_unificada (string): Versão unificada/agrupada da resposta
 *     - qtd_votos (int): Quantidade de usuários que deram essa resposta específica
 * 
 * - manual_items (array): Lista de opções criadas manualmente pelo administrador
 *   * text (string): Texto da opção exibido ao usuário
 *   * vs_valor_real (string): Valor real da opção (igual ao text para opções manuais)
 * 
 * - imported_items (array): Lista de opções selecionadas das respostas importadas
 *   * text (string): Texto da resposta importada selecionada
 *   * vs_valor_real (string): Valor original da resposta importada
 * 
 * FUNCIONAMENTO DA ESTRUTURA:
 * ===============================
 * 
 * 1. SEPARAÇÃO DE TIPOS DE OPÇÕES:
 *    - manual_items: Opções criadas diretamente pelo administrador
 *    - imported_items: Opções selecionadas das respostas de votações anteriores
 *    - Ambos os tipos são renderizados no formulário final da votação
 * 
 * 2. PERSISTÊNCIA DE DADOS:
 *    - A estrutura garante que opções importadas mantenham sua classe CSS 'imported_question'
 *    - O campo 'vs_valor_real' preserva o valor original das respostas importadas
 *    - Opções manuais e importadas são tratadas de forma diferenciada na interface
 * 
 * 3. VALIDAÇÃO DE DUPLICATAS:
 *    - O sistema verifica duplicatas baseado no campo 'vs_valor_real'
 *    - Impede a adição de opções com valores reais idênticos
 *    - Funciona tanto para opções manuais quanto importadas
 * 
 * 4. RENDERIZAÇÃO NO FRONTEND:
 *    - Opções de manual_items são renderizadas sem classe especial
 *    - Opções de imported_items recebem a classe 'imported_question'
 *    - Ambos os tipos mantêm o campo 'vs-valor-real' para processamento
 * 
 * ESTRUTURA DE RESPOSTA AJAX PADRÃO
 * =================================
 * 
 * Todas as operações AJAX seguem este padrão de resposta:
 * 
 * SUCESSO:
 * {
 *   "success": true,
 *   "data": {
 *     "user_name": "João Silva",
 *     "data_envio": "15/12/2024 14:30",
 *     "respostas": [
 *       {
 *         "question": "Qual sua opinião sobre o projeto?",
 *         "answer": "Aprovo totalmente"
 *       },
 *       {
 *         "question": "Comentários adicionais:",
 *         "answer": "Projeto muito bem estruturado"
 *       }
 *     ]
 *   }
 * }
 * 
 * ERRO:
 * {
 *   "success": false,
 *   "data": "Mensagem de erro detalhada"
 * }
 * 
 * ESTRUTURA DE DADOS DE UNIFICAÇÃO
 * ================================
 * 
 * Dados enviados via AJAX para unificação:
 * {
 *   "votacao_id": 123,
 *   "question_index": 0,
 *   "linhas": "[{\"resposta_id\": 456, \"question_index\": 0}, {\"resposta_id\": 789, \"question_index\": 0}]",
 *   "nova_unificada": "Categoria Unificada",
 *   "vs_nonce": "abc123def456"
 * }
 * 
 * SISTEMA DE METADADOS COMPLETO
 * =============================
 * 
 * VOTAÇÃO (CPT: votacoes):
 * - vs_questions: Array JSON com estrutura de perguntas (ver acima)
 * - _vs_ano: Ano da votação (int)
 * - _vs_status: Status atual (string: "aberta"|"em-pausa"|"encerrada")
 * - _vs_codigo: Código único da votação (string)
 * - _vs_data_inicio: Data de início (Y-m-d)
 * - _vs_data_fim: Data de encerramento (Y-m-d)
 * - _vs_qtd_votos: Quantidade total de votos (int)
 * - _vs_votantes: Array com IDs dos usuários que votaram
 * - _vs_permitir_edicao: Permite edição de votos (boolean)
 * 
 * RESPOSTA (CPT: votacao_resposta):
 * - vs_votacao_id: ID da votação relacionada (int)
 * - vs_usuario_id: ID do usuário que respondeu (int)
 * - vs_respostas_detalhadas: Array JSON com respostas originais (ver acima)
 * - vs_resposta_unificada: Array JSON com respostas unificadas (ver acima)
 * - vs_data_envio: Data/hora do envio (Y-m-d H:i:s)
 * 
 * ===============================================================================
 * SEÇÃO 3: FLUXO DE DADOS E PROCESSAMENTO
 * ===============================================================================
 * 
 * FLUXO DE CRIAÇÃO DE VOTAÇÃO
 * ===========================
 * 
 * 1. INTERFACE ADMIN:
 *    - Arquivo: metaboxes/vs-metabox-questions.php
 *    - Template: templates/admin/template-metabox-question-row.php
 *    - JavaScript: assets/js/admin.js, assets/js/admin/imported-answers.js
 * 
 * 2. SALVAMENTO:
 *    - Perguntas são serializadas em JSON no metadado 'vs_questions'
 *    - Validação de campos obrigatórios e tipos
 *    - Processamento de respostas importadas via vs_get_imported_vote_data()
 * 
 * 3. TIPOS DE PERGUNTA SUPORTADOS:
 *    - texto: Campo input simples
 *    - textarea: Área de texto multilinha
 *    - radio: Seleção única com botões radio
 *    - checkbox: Seleção múltipla com checkboxes
 *    - select: Lista suspensa
 *    - imported_vote: Importa respostas de votação anterior
 * 
 * FLUXO DE SUBMISSÃO DE RESPOSTA
 * ==============================
 * 
 * 1. RECEPÇÃO:
 *    - Arquivo: includes/core/submission/vs-process-voting.php
 *    - Dados recebidos via POST do formulário
 *    - Validação de nonces e permissões
 * 
 * 2. PROCESSAMENTO:
 *    - Função: vs_format_user_answers() em helpers/vs-utils-data.php
 *    - Sanitização de dados de entrada
 *    - Formatação baseada no tipo de pergunta
 *    - Validação de campos obrigatórios
 * 
 * 3. SALVAMENTO:
 *    - Criação do CPT 'votacao_resposta'
 *    - Função: vs_update_response_metadata()
 *    - Atualização de contadores: vs_update_votantes()
 *    - Geração de token para página de agradecimento
 * 
 * FLUXO DE UNIFICAÇÃO DE RESPOSTAS
 * ================================
 * 
 * 1. COLETA DE DADOS:
 *    - AJAX Handler: includes/ajax/vs-handle-get-unificacao-group.php
 *    - Busca respostas por votação e pergunta específica
 *    - Agrupa respostas similares para apresentação
 * 
 * 2. INTERFACE DE UNIFICAÇÃO:
 *    - Template: templates/admin/template-results-unificacao.php
 *    - JavaScript: assets/js/ajax/vs-handle-get-unificacao-group.js
 *    - Duas colunas: respostas individuais e grupos unificados
 * 
 * 3. PROCESSAMENTO DE UNIFICAÇÃO:
 *    - AJAX Handler: includes/ajax/vs-handle-update-unificacao.php
 *    - Função: vs_update_resposta_unificada_indices()
 *    - Atualiza metadado 'vs_resposta_unificada' em lote
 *    - Mantém respostas originais intactas
 * 
 * FLUXO DE IMPORTAÇÃO DE RESPOSTAS
 * ================================
 * 
 * 1. SELEÇÃO DE VOTAÇÃO ANTERIOR:
 *    - Modal: templates/admin/template-metabox-question-row.php
 *    - AJAX Handler: includes/ajax/vs-handle-votacao-anterior.php
 *    - Filtros por ano, evento e status
 * 
 * 2. PROCESSAMENTO DE DADOS:
 *    - Função: vs_get_imported_vote_data() em helpers/vs-utils-data.php
 *    - Coleta respostas detalhadas e unificadas
 *    - Gera estatísticas por resposta
 *    - Formata dados para interface
 * 
 * 3. INTEGRAÇÃO NA PERGUNTA:
 *    - Campo 'imported_answers' armazena JSON com dados
 *    - Campo 'valores_reais' mantém respostas originais
 *    - Interface permite seleção de respostas específicas
 * 
 * ===============================================================================
 * SEÇÃO 4: SHORTCODES E TEMPLATES
 * ===============================================================================
 * 
 * SHORTCODES PRINCIPAIS
 * ====================
 * 
 * 1. [votacao_formulario id="123"]
 *    - Arquivo: includes/frontend/shortcodes/vs-shortcode-votacao-formulario.php
 *    - Template: templates/public/template-voting-form.php
 *    - Funcionalidades: validação, edição, submissão
 * 
 * 2. [votacao_obrigado]
 *    - Arquivo: includes/frontend/shortcodes/vs-shortcode-thank-you.php
 *    - Template: templates/public/template-thank-you.php
 *    - Validação por token temporário
 * 
 * 3. [votacoes_feed]
 *    - Arquivo: includes/frontend/shortcodes/vs-shortcode-votacoes-generic.php
 *    - Template: templates/public/template-votacoes-feed.php
 *    - Filtros: ano, status, código, evento
 * 
 * 4. [votacoes_home_feed]
 *    - Arquivo: includes/frontend/shortcodes/vs-shortcode-votacoes-home-feed.php
 *    - Template: templates/public/template-home-feed.php
 *    - Cards visuais agrupados por ano/evento
 * 
 * SHORTCODES DA ÁREA DO USUÁRIO
 * =============================
 * 
 * 5. [votacoes_usuario_ativas]
 *    - Lista votações que o usuário respondeu e ainda podem ser editadas
 *    - Restrição: apenas usuários logados
 *    - Template: templates/public/template-votacoes-usuario-ativas.php
 *    - CSS: assets/css/public/vs-user-votacoes.css
 * 
 * 6. [votacoes_usuario_encerradas]
 *    - Lista votações que o usuário participou e já estão encerradas
 *    - Template: templates/public/template-votacoes-usuario-encerradas.php
 *    - Funcionalidade: visualização de respostas via modal
 * 
 * 7. [votacoes_disponiveis]
 *    - Lista votações em aberto que o usuário ainda não participou
 *    - Template: templates/public/template-votacoes-disponiveis.php
 *    - Filtro: votações abertas sem participação do usuário
 * 
 * 8. [votacoes_display]
 *    - Exibe lista genérica de votações com filtros
 *    - Suporte a múltiplos layouts (lista, cards)
 *    - Parâmetros configuráveis via shortcode
 * 
 * ===============================================================================
 * SEÇÃO 5: SISTEMA AJAX E HANDLERS
 * ===============================================================================
 * 
 * HANDLERS AJAX PRINCIPAIS
 * =======================
 * 
 * 1. vs_get_user_votes
 *    - Arquivo: includes/ajax/get-user-votes.php
 *    - Função: Recupera votos de usuário específico
 *    - Retorna: dados formatados para modal de visualização
 * 
 * 2. vs_get_unificacao_group
 *    - Arquivo: includes/ajax/vs-handle-get-unificacao-group.php
 *    - Função: Busca grupos para unificação de respostas
 *    - Retorna: respostas agrupadas por similaridade
 * 
 * 3. vs_update_unificacao
 *    - Arquivo: includes/ajax/vs-handle-update-unificacao.php
 *    - Função: Atualiza unificações em lote
 *    - Processa: múltiplas respostas simultaneamente
 * 
 * 4. vs_handle_votacao_anterior
 *    - Arquivo: includes/ajax/vs-handle-votacao-anterior.php
 *    - Função: Busca votações anteriores para importação
 *    - Filtros: ano, evento, status, busca textual
 * 
 * ESTRUTURAS DE DADOS AJAX
 * ========================
 * 
 * Requisição de busca de votações:
 * {
 *   "action": "vs_handle_votacao_anterior",
 *   "vs_nonce": "abc123",
 *   "ano": "2024",
 *   "evento": "evento-slug",
 *   "status": "encerrada",
 *   "busca": "termo de busca"
 * }
 * 
 * Resposta de votações encontradas:
 * {
 *   "success": true,
 *   "data": {
 *     "votacoes": [
 *       {
 *         "id": 123,
 *         "title": "Título da Votação",
 *         "codigo": "VOT-2024-001",
 *         "status": "encerrada",
 *         "data_fim": "2024-01-31",
 *         "evento": "Nome do Evento",
 *         "qtd_votos": 45
 *       }
 *     ]
 *   }
 * }
 * 
 * ===============================================================================
 * SEÇÃO 6: SEGURANÇA E VALIDAÇÃO
 * ===============================================================================
 * 
 * SISTEMA DE NONCES
 * ================
 * 
 * Arquivo: includes/core/security/vs-nonce-actions.php
 * 
 * Ações protegidas:
 * - VS_Nonce_Actions::AJAX_GENERAL: Operações AJAX gerais
 * - VS_Nonce_Actions::VOTING_SUBMISSION: Submissão de votos
 * - VS_Nonce_Actions::ADMIN_OPERATIONS: Operações administrativas
 * 
 * Validação:
 * - Função: vs_verify_ajax_nonce_or_die()
 * - Todas as operações críticas verificam nonces
 * - Prevenção contra ataques CSRF
 * 
 * SANITIZAÇÃO DE DADOS
 * ===================
 * 
 * ENTRADA:
 * - sanitize_text_field(): Campos de texto simples
 * - wp_kses_post(): Conteúdo HTML permitido
 * - absint(): Números inteiros
 * - sanitize_email(): Endereços de email
 * 
 * SAÍDA:
 * - esc_html(): Texto para HTML
 * - esc_attr(): Atributos HTML
 * - wp_json_encode(): Dados JSON seguros
 * - esc_url(): URLs
 * 
 * VERIFICAÇÃO DE PERMISSÕES
 * ========================
 * 
 * CAPABILITIES UTILIZADAS:
 * - manage_options: Administração completa
 * - read: Usuários logados (votação)
 * - edit_posts: Edição de conteúdo
 * 
 * VERIFICAÇÕES:
 * - current_user_can(): Verifica capabilities
 * - is_user_logged_in(): Status de login
 * - get_current_user_id(): ID do usuário atual
 * 
 * ===============================================================================
 * SEÇÃO 7: PERFORMANCE E OTIMIZAÇÃO
 * ===============================================================================
 * 
 * ESTRATÉGIAS DE CACHE
 * ===================
 * 
 * 1. METADADOS:
 *    - Cache automático do WordPress para post_meta
 *    - Uso de get_post_meta() com single=true
 *    - Evita consultas desnecessárias
 * 
 * 2. CONSULTAS OTIMIZADAS:
 *    - WP_Query com parâmetros específicos
 *    - meta_query otimizada para relacionamentos
 *    - fields='ids' quando apenas IDs são necessários
 * 
 * 3. TRANSIENTS:
 *    - vs_generate_thank_you_token(): Token temporário (5 min)
 *    - Cache de consultas pesadas quando aplicável
 * 
 * CARREGAMENTO CONDICIONAL
 * =======================
 * 
 * ASSETS CSS/JS:
 * - Carregamento apenas nas páginas necessárias
 * - Função: vs_enqueue_conditional_assets()
 * - Detecção de shortcodes na página
 * - Minificação em produção
 * 
 * PAGINAÇÃO
 * =========
 * 
 * IMPLEMENTAÇÃO:
 * - posts_per_page: Limite configurável
 * - Navegação com prev/next
 * - Contadores de total de itens
 * - URLs amigáveis para SEO
 * 
 * ===============================================================================
 * SEÇÃO 8: HOOKS, FILTROS E EXTENSIBILIDADE
 * ===============================================================================
 * 
 * ACTIONS DISPONÍVEIS
 * ==================
 * 
 * - vs_after_vote_submission: Executado após submissão bem-sucedida
 *   Parâmetros: $votacao_id, $user_id, $respostas_formatadas
 * 
 * - vs_before_vote_update: Executado antes de atualizar voto existente
 *   Parâmetros: $resposta_id, $votacao_id, $user_id
 * 
 * - vs_voting_closed: Executado quando votação é encerrada automaticamente
 *   Parâmetros: $votacao_id, $data_encerramento
 * 
 * FILTERS DISPONÍVEIS
 * ==================
 * 
 * - vs_voting_form_fields: Modifica campos do formulário antes da renderização
 *   Parâmetros: $fields_html, $questions, $votacao_id
 * 
 * - vs_export_data: Modifica dados antes da exportação CSV
 *   Parâmetros: $export_data, $votacao_id, $format
 * 
 * - vs_unification_groups: Modifica grupos de unificação
 *   Parâmetros: $groups, $votacao_id, $question_index
 * 
 * EXEMPLO DE USO:
 * 
 * // Adicionar ação personalizada após votação
 * add_action('vs_after_vote_submission', function($votacao_id, $user_id, $respostas) {
 *     // Enviar email de confirmação
 *     // Integrar com sistema externo
 *     // Log personalizado
 * }, 10, 3);
 * 
 * // Modificar dados de exportação
 * add_filter('vs_export_data', function($data, $votacao_id) {
 *     // Adicionar colunas personalizadas
 *     // Formatar dados específicos
 *     return $data;
 * }, 10, 2);
 * 
 * ===============================================================================
 * SEÇÃO 9: CONSIDERAÇÕES PARA DESENVOLVIMENTO
 * ===============================================================================
 * 
 * EXTENSIBILIDADE
 * ==============
 * 
 * O sistema foi projetado para ser extensível através de:
 * - Hooks e filtros em pontos estratégicos
 * - Classes modulares com responsabilidades específicas
 * - Templates customizáveis via tema
 * - Estrutura de dados flexível em JSON
 * 
 * COMPATIBILIDADE
 * ==============
 * 
 * REQUISITOS MÍNIMOS:
 * - WordPress 5.0+
 * - PHP 7.4+
 * - MySQL 5.6+
 * - 128MB PHP Memory Limit
 * 
 * TEMAS TESTADOS:
 * - Divi Builder (compatibilidade total)
 * - Twenty Twenty-Three
 * - Astra
 * - GeneratePress
 * 
 * DEBUGGING E LOGS
 * ===============
 * 
 * ATIVAÇÃO:
 * - WP_DEBUG = true em wp-config.php
 * - Logs em wp-content/debug.log
 * - Console do navegador para AJAX
 * 
 * PONTOS DE LOG:
 * - Submissão de votações
 * - Operações de unificação
 * - Erros de validação
 * - Falhas de AJAX
 * 
 * TESTES RECOMENDADOS
 * ==================
 * 
 * FUNCIONALIDADES CRÍTICAS:
 * - Criação e edição de votações
 * - Submissão de respostas (todos os tipos de campo)
 * - Sistema de unificação
 * - Exportação CSV
 * - Área do usuário (shortcodes novos)
 * - Importação de respostas anteriores
 * 
 * CENÁRIOS DE TESTE:
 * - Usuários não logados
 * - Votações encerradas
 * - Campos obrigatórios
 * - Respostas duplicadas
 * - Performance com muitos dados
 * - Responsividade mobile
 * 
 * PRÓXIMOS DESENVOLVIMENTOS SUGERIDOS
 * ==================================
 * 
 * FUNCIONALIDADES:
 * - Sistema de notificações por email
 * - Relatórios avançados com gráficos
 * - API REST para integração externa
 * - Sistema de templates personalizáveis
 * - Backup automático de dados
 * - Logs de auditoria detalhados
 * - Dashboard personalizado para usuários
 * - Sistema de favoritos/bookmarks
 * - Filtros avançados na área do usuário
 * - Integração com outros plugins populares
 * 
 * MELHORIAS TÉCNICAS:
 * - Cache avançado com Redis/Memcached
 * - Otimização de queries complexas
 * - Lazy loading para listas grandes
 * - Compressão de dados JSON
 * - Indexação de banco de dados
 * - Monitoramento de performance
 * 
 * ===============================================================================
 * FIM DA DOCUMENTAÇÃO TÉCNICA
 * ===============================================================================
 */

// Fim da documentação
?>

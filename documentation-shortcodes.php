<?php
/**
 * DOCUMENTAÇÃO DOS SHORTCODES - VOTING SYSTEM v2.250730.1
 * 
 * Este arquivo documenta todos os shortcodes disponíveis no sistema de votação,
 * incluindo parâmetros, funcionalidades e exemplos de uso.
 */

defined('ABSPATH') || exit;

/**
 * ===== SHORTCODES DISPONÍVEIS =====
 * 
 * O sistema possui 8 shortcodes principais organizados em categorias:
 * 
 * FORMULÁRIOS E AGRADECIMENTO:
 * 1. [votacao_formulario] - Formulário de votação
 * 2. [votacao_obrigado] - Página de agradecimento
 * 
 * FEEDS PÚBLICOS:
 * 3. [votacoes_feed] - Feed simples
 * 4. [votacoes_home_feed] - Feed principal com cards
 * 5. [votacoes_display] - Shortcode genérico (NOVO)
 * 
 * ÁREA DO USUÁRIO:
 * 6. [votacoes_usuario_ativas] - Votações ativas do usuário
 * 7. [votacoes_usuario_encerradas] - Votações encerradas do usuário
 * 8. [votacoes_disponiveis] - Votações disponíveis para participar
 */

/**
 * ===== 1. SHORTCODE: [votacao_formulario] =====
 * 
 * Arquivo: vs-shortcode-voting-form.php
 * Função: vs_votacao_shortcode()
 * 
 * DESCRIÇÃO:
 * Exibe o formulário de uma votação específica com validação e submissão.
 * 
 * PARÂMETROS:
 * - id (obrigatório): ID da votação a ser exibida
 * 
 * FUNCIONALIDADES:
 * - Validação de usuário logado
 * - Verificação de status da votação
 * - Detecção de voto anterior (para edição)
 * - Formulário dinâmico baseado nas perguntas
 * - Submissão via AJAX ou POST
 * - Redirecionamento para página de agradecimento
 * 
 * EXEMPLOS:
 * [votacao_formulario id="123"]
 * 
 * TEMPLATES UTILIZADOS:
 * - template-voting-form.php
 * - template-voting-form-fields.php
 */

/**
 * ===== 2. SHORTCODE: [votacao_obrigado] =====
 * 
 * Arquivo: vs-shortcode-thank-you.php
 * Função: vs_shortcode_votacao_obrigado()
 * 
 * DESCRIÇÃO:
 * Página de agradecimento exibida após submissão de voto.
 * Valida token temporário e exibe resumo das respostas.
 * 
 * PARÂMETROS:
 * Nenhum (obtém dados via URL: ?votacao_id=X&token=Y)
 * 
 * FUNCIONALIDADES:
 * - Validação de token temporário (5 minutos)
 * - Exibição do resumo das respostas
 * - Informações da votação
 * - Links para outras ações
 * 
 * EXEMPLOS:
 * [votacao_obrigado]
 * 
 * TEMPLATES UTILIZADOS:
 * - template-thank-you.php
 */

/**
 * ===== 3. SHORTCODE: [votacoes_feed] =====
 * 
 * Arquivo: vs-shortcode-votacoes-feed.php
 * Função: vs_shortcode_votacoes_feed()
 * 
 * DESCRIÇÃO:
 * Feed simples de votações com filtros básicos.
 * Layout de lista compacto.
 * 
 * PARÂMETROS:
 * - ano: Filtrar por ano específico
 * - status: Filtrar por status ('aberta', 'encerrada', 'em-pausa')
 * - evento: Filtrar por slug do evento (taxonomia)
 * - posts_per_page: Número de itens por página (padrão: 10)
 * - orderby: Campo de ordenação (padrão: 'date')
 * - order: Direção da ordenação (padrão: 'DESC')
 * 
 * EXEMPLOS:
 * [votacoes_feed]
 * [votacoes_feed ano="2024" status="aberta"]
 * [votacoes_feed evento="eleicoes" posts_per_page="5"]
 * 
 * TEMPLATES UTILIZADOS:
 * - template-votacoes-feed.php
 */

/**
 * ===== 4. SHORTCODE: [votacoes_home_feed] =====
 * 
 * Arquivo: vs-shortcode-votacoes-home-feed.php
 * Função: vs_shortcode_home_feed()
 * 
 * DESCRIÇÃO:
 * Feed principal com cards visuais, agrupamento e filtros avançados.
 * Interface responsiva com design moderno.
 * 
 * PARÂMETROS:
 * FILTROS E LIMITAÇÃO:
 * - limit: Número máximo de votações (padrão: 20)
 * - per_page: Itens por página (padrão: 6)
 * - show_status: Filtro por status (padrão: 'all')
 * - hide_encerradas: Ocultar encerradas (padrão: false)
 * - only_active: Apenas ativas (padrão: false)
 * - exclude_ids: IDs para excluir ('1,2,3')
 * - include_ids: Apenas estes IDs ('4,5,6')
 * - show_uncategorized: Mostrar sem categoria (padrão: true)
 * 
 * EXIBIÇÃO:
 * - show_excerpts: Mostrar resumos (padrão: true)
 * - excerpt_length: Palavras no resumo (padrão: 20)
 * - show_participation_badge: Badge "Já votou" (padrão: true)
 * - show_time_remaining: Tempo restante (padrão: true)
 * - show_actions: Botões de ação (padrão: 'visible')
 * 
 * FUNCIONALIDADES:
 * - Agrupamento por ano e evento
 * - Filtros por URL (?evento=slug&ano=2024)
 * - Detecção de participação do usuário
 * - Badges de status e participação
 * - Cálculo de tempo restante
 * - Botão "Editar voto" quando aplicável
 * 
 * EXEMPLOS:
 * [votacoes_home_feed]
 * [votacoes_home_feed limit="15" hide_encerradas="true"]
 * [votacoes_home_feed show_participation_badge="false" excerpt_length="30"]
 * 
 * TEMPLATES UTILIZADOS:
 * - template-home-feed.php
 * - template-home-feed-cards.php
 * - template-home-feed-filtros.php
 */

/**
 * ===== 5. SHORTCODE: [votacoes_display] (NOVO - GENÉRICO) =====
 * 
 * Arquivo: vs-shortcode-votacoes-generic.php
 * Função: vs_shortcode_votacoes_generic()
 * 
 * DESCRIÇÃO:
 * Shortcode flexível e poderoso que unifica funcionalidades dos outros shortcodes.
 * Múltiplos layouts, filtros avançados e alta configurabilidade.
 * 
 * PARÂMETROS COMPLETOS:
 * 
 * FILTROS E LIMITAÇÃO:
 * - limit: Número máximo de votações (padrão: 10)
 * - per_page: Itens por página para paginação (padrão: 6)
 * - show_status: Filtro por status - 'all', 'aberta', 'encerrada', 'em-pausa' (padrão: 'all')
 * - hide_encerradas: Ocultar encerradas - true/false (padrão: false)
 * - only_active: Apenas ativas - true/false (padrão: false)
 * - exclude_ids: IDs para excluir - '1,2,3'
 * - include_ids: Apenas estes IDs - '4,5,6'
 * - show_uncategorized: Mostrar sem categoria - true/false (padrão: true)
 * 
 * EXIBIÇÃO E CONTEÚDO:
 * - show_excerpts: Mostrar resumos - true/false (padrão: true)
 * - excerpt_length: Palavras no resumo (padrão: 20)
 * - show_participation_badge: Badge "Já votou" - true/false (padrão: true)
 * - show_time_remaining: Tempo restante - true/false (padrão: true)
 * - show_actions: Botões de ação - 'visible', 'hidden' (padrão: 'visible')
 * 
 * LAYOUT E AGRUPAMENTO:
 * - layout: Tipo de layout - 'cards', 'list', 'grouped' (padrão: 'cards')
 * - group_by: Agrupar por - 'none', 'year', 'category', 'status' (padrão: 'none')
 * - show_filters: Mostrar filtros - true/false (padrão: false)
 * 
 * ORDENAÇÃO:
 * - orderby: Campo - 'date', 'title', 'meta_value' (padrão: 'date')
 * - order: Direção - 'ASC', 'DESC' (padrão: 'DESC')
 * - meta_key: Chave do meta field (quando orderby='meta_value')
 * 
 * COMPATIBILIDADE:
 * - enable_url_filters: Filtros por URL - true/false (padrão: false)
 * 
 * EXEMPLOS:
 * // Básico - exibe 10 votações em cards
 * [votacoes_display]
 * 
 * // Lista apenas votações ativas
 * [votacoes_display only_active="true" layout="list"]
 * 
 * // Cards agrupados por ano com filtros
 * [votacoes_display group_by="year" show_filters="true" limit="20"]
 * 
 * // Votações específicas sem botões
 * [votacoes_display include_ids="1,2,3" show_actions="hidden"]
 * 
 * // Feed completo com todas as funcionalidades
 * [votacoes_display limit="50" group_by="year" show_filters="true" show_time_remaining="true"]
 * 
 * TEMPLATES UTILIZADOS:
 * - template-votacoes-generic-cards.php
 * - template-votacoes-generic-list.php
 * - template-votacoes-generic-fallback.php
 * - template-votacoes-generic-card-item.php
 * - template-votacoes-generic-list-item.php
 */

/**
 * ===== 6. SHORTCODE: [votacoes_usuario_ativas] =====
 * 
 * Arquivo: vs-shortcode-user-votacoes.php
 * Função: vs_shortcode_votacoes_usuario_ativas()
 * 
 * DESCRIÇÃO:
 * Lista votações que o usuário respondeu e ainda podem ser editadas.
 * Área restrita para usuários logados.
 * 
 * PARÂMETROS:
 * Nenhum (comportamento fixo)
 * 
 * FUNCIONALIDADES:
 * - Verificação de login obrigatória
 * - Filtra apenas votações não encerradas
 * - Exibe resumo das respostas do usuário
 * - Botão "Editar Voto" se permitido
 * - Botão "Ver Respostas" padrão
 * - Verifica meta field 'vs_permitir_edicao'
 * 
 * EXEMPLOS:
 * [votacoes_usuario_ativas]
 * 
 * TEMPLATES UTILIZADOS:
 * - template-votacoes-usuario-ativas.php
 */

/**
 * ===== 7. SHORTCODE: [votacoes_usuario_encerradas] =====
 * 
 * Arquivo: vs-shortcode-user-votacoes.php
 * Função: vs_shortcode_votacoes_usuario_encerradas()
 * 
 * DESCRIÇÃO:
 * Lista votações que o usuário participou e já estão encerradas.
 * Área restrita para usuários logados.
 * 
 * PARÂMETROS:
 * Nenhum (comportamento fixo)
 * 
 * FUNCIONALIDADES:
 * - Verificação de login obrigatória
 * - Filtra apenas votações encerradas
 * - Exibe status "Encerrada"
 * - Resumo das respostas do usuário
 * - Apenas botão "Ver Respostas"
 * - Data da votação e da resposta
 * 
 * EXEMPLOS:
 * [votacoes_usuario_encerradas]
 * 
 * TEMPLATES UTILIZADOS:
 * - template-votacoes-usuario-encerradas.php
 */

/**
 * ===== 8. SHORTCODE: [votacoes_disponiveis] =====
 * 
 * Arquivo: vs-shortcode-user-votacoes.php
 * Função: vs_shortcode_votacoes_disponiveis()
 * 
 * DESCRIÇÃO:
 * Lista votações em aberto que o usuário ainda não participou.
 * Área restrita para usuários logados.
 * 
 * PARÂMETROS:
 * Nenhum (comportamento fixo)
 * 
 * FUNCIONALIDADES:
 * - Verificação de login obrigatória
 * - Filtra votações não respondidas pelo usuário
 * - Exibe prazo de encerramento
 * - Botão "Participar" destacado
 * - Descrição da votação
 * - Tempo restante calculado
 * 
 * EXEMPLOS:
 * [votacoes_disponiveis]
 * 
 * TEMPLATES UTILIZADOS:
 * - template-votacoes-disponiveis.php
 */

/**
 * ===== MIGRAÇÃO E COMPATIBILIDADE =====
 * 
 * SUBSTITUINDO SHORTCODES ANTIGOS COM [votacoes_display]:
 * 
 * // De: [votacoes_feed]
 * // Para:
 * [votacoes_display layout="list" show_filters="false"]
 * 
 * // De: [votacoes_home_feed]
 * // Para:
 * [votacoes_display group_by="year" show_filters="true" layout="cards"]
 * 
 * // De: [votacoes_usuario_ativas] (conceito similar)
 * // Para:
 * [votacoes_display only_active="true" show_participation_badge="true"]
 * 
 * // De: [votacoes_disponiveis] (conceito similar)
 * // Para:
 * [votacoes_display hide_encerradas="true" show_participation_badge="false"]
 */

/**
 * ===== FUNCIONALIDADES AVANÇADAS =====
 * 
 * TEMPO RESTANTE:
 * - Calcula automaticamente baseado no meta field '_vs_data_fim'
 * - Exibe em dias, horas ou "Menos de 1 hora"
 * - Mostra "Encerrada" para votações vencidas
 * - Função: vs_calculate_time_remaining()
 * 
 * BADGES DE PARTICIPAÇÃO:
 * - "✓ Já votou" para votações respondidas
 * - Bordas verdes em cards de votações respondidas
 * - Controle via atributo 'show_participation_badge'
 * - Verificação via query de posts 'votacao_resposta'
 * 
 * BOTÃO EDITAR VOTO:
 * - Aparece apenas se usuário já votou
 * - Verifica meta field 'vs_permitir_edicao'
 * - Não aparece em votações encerradas
 * - Redireciona para formulário com dados preenchidos
 * 
 * FILTROS INTELIGENTES:
 * - Filtro por categoria (taxonomia 'eventos')
 * - Filtro por ano (meta field '_vs_ano')
 * - Suporte a filtros via URL (?evento=slug&ano=2024)
 * - Botão "Limpar filtros" condicional
 * - JavaScript para interatividade
 * 
 * AGRUPAMENTO:
 * - Por ano (decrescente)
 * - Por categoria (alfabético, "Sem Categoria" por último)
 * - Por status (aberta, em-pausa, encerrada)
 * - Sem agrupamento (lista simples)
 * 
 * LAYOUTS RESPONSIVOS:
 * - Cards: Grid responsivo com hover effects
 * - Lista: Layout horizontal compacto
 * - Fallback: Layout simples para compatibilidade
 * - CSS específico para cada layout
 */

/**
 * ===== ESTILOS CSS =====
 * 
 * ARQUIVOS CSS CARREGADOS:
 * - vs-votacoes-generic.css: Estilos do shortcode genérico
 * - vs-votacoes-home.css: Estilos específicos do home feed
 * - vs-user-votacoes.css: Estilos da área do usuário
 * - vs-voting-form.css: Estilos do formulário de votação
 * 
 * CLASSES CSS PRINCIPAIS:
 * - .vs-generic-container: Container principal
 * - .vs-layout-cards: Layout de cards
 * - .vs-layout-list: Layout de lista
 * - .vs-card: Card individual
 * - .vs-card-voted: Card de votação respondida
 * - .vs-status-badge: Badge de status
 * - .vs-participation-badge: Badge de participação
 * - .vs-btn: Botões do sistema
 * - .vs-filters: Container de filtros
 * - .vs-group-header: Cabeçalho de grupo
 * 
 * CARREGAMENTO CONDICIONAL:
 * - CSS carregado apenas quando shortcode é usado
 * - Classe VS_CSS_Conditional_Loader gerencia carregamento
 * - Evita CSS desnecessário em páginas sem shortcodes
 */

/**
 * ===== HOOKS E FILTROS =====
 * 
 * FILTROS DISPONÍVEIS:
 * - vs_generic_query_args: Modifica argumentos da query
 * - vs_generic_votacoes_data: Modifica dados das votações
 * - vs_generic_template_data: Modifica dados do template
 * - vs_voting_form_fields: Modifica campos do formulário
 * - vs_export_data: Modifica dados de exportação
 * - vs_unification_groups: Modifica grupos de unificação
 * 
 * ACTIONS DISPONÍVEIS:
 * - vs_before_vote_submit: Antes de submeter voto
 * - vs_after_vote_submit: Após submeter voto
 * - vs_before_vote_update: Antes de atualizar voto
 * - vs_after_vote_update: Após atualizar voto
 * - vs_shortcode_loaded: Quando shortcode é carregado
 * 
 * EXEMPLOS DE USO:
 * 
 * // Modificar query do shortcode genérico
 * add_filter('vs_generic_query_args', function($args) {
 *     $args['meta_query'][] = [
 *         'key' => 'custom_field',
 *         'value' => 'custom_value'
 *     ];
 *     return $args;
 * });
 * 
 * // Adicionar dados customizados
 * add_filter('vs_generic_votacoes_data', function($votacoes) {
 *     foreach($votacoes as &$votacao) {
 *         $votacao['custom_data'] = get_post_meta($votacao['id'], 'custom_field', true);
 *     }
 *     return $votacoes;
 * });
 */

/**
 * ===== FUNÇÕES UTILITÁRIAS =====
 * 
 * PRINCIPAIS FUNÇÕES HELPER:
 * 
 * vs_get_voting_questions($votacao_id)
 * - Retorna array de perguntas da votação
 * 
 * vs_check_votacao_status($data_fim)
 * - Verifica se votação está encerrada por data
 * 
 * vs_calculate_time_remaining($data_fim)
 * - Calcula tempo restante para encerramento
 * 
 * vs_generate_response_summary($respostas, $questions)
 * - Gera resumo das respostas do usuário
 * 
 * vs_user_has_voted($user_id, $votacao_id)
 * - Verifica se usuário já votou na votação
 * 
 * vs_get_user_response($user_id, $votacao_id)
 * - Obtém resposta do usuário para votação
 * 
 * vs_format_answer($resposta)
 * - Formata resposta (array ou string)
 * 
 * vs_ensure_css_for_shortcode($shortcode_name)
 * - Garante carregamento do CSS do shortcode
 */

<?php
/**
 * Template do conteúdo do widget de população
 * 
 * @package VotingSystem\SampleData\Dashboard
 */

defined( 'ABSPATH' ) || exit;

$stats = VS_Dashboard_Population_Widget::get_stats();
?>

<div class="vs-population-widget">
    <!-- Loading indicator -->
    <div class="vs-loading" style="display: none;">
        <div class="vs-loading-content">
            <div class="vs-spinner"></div>
            <p>Processando...</p>
        </div>
    </div>
    
    <!-- Estatísticas -->
    <div class="vs-stats-grid">
        <div class="vs-stat-card">
            <div class="vs-stat-number"><?php echo $stats['total_votacoes']; ?></div>
            <div class="vs-stat-label">Total de Votações</div>
        </div>
        
        <div class="vs-stat-card vs-sample">
            <div class="vs-stat-number"><?php echo $stats['votacoes_exemplo']; ?></div>
            <div class="vs-stat-label">Votações de Exemplo</div>
        </div>
        
        <div class="vs-stat-card vs-sample">
            <div class="vs-stat-number"><?php echo $stats['eventos_exemplo']; ?></div>
            <div class="vs-stat-label">Eventos de Exemplo</div>
        </div>
        
        <div class="vs-stat-card vs-sample">
            <div class="vs-stat-number"><?php echo $stats['usuarios_exemplo']; ?></div>
            <div class="vs-stat-label">Usuários de Teste</div>
        </div>
    </div>

    <!-- Botão Criar/Limpar Respostas Automáticas (só aparece se há dados populados) -->
    <?php if ($stats['has_sample_data']): ?>
    <div class="vs-auto-responses-section">
        <div class="vs-auto-responses-card">
            <div class="vs-auto-responses-content">
                <?php if (!$stats['has_sample_responses']): ?>
                    <h3>🤖 Criar Respostas Automáticas</h3>
                    <p>Gera respostas automáticas para todas as votações marcadas como "exemplo", simulando participação dos usuários de teste.</p>
                    <ul class="vs-action-list">
                        <li>✓ Respostas apenas para votações de exemplo</li>
                        <li>✓ Utiliza os usuários de teste criados</li>
                        <li>✓ Simula participação realista (70-85%)</li>
                        <li>✓ 25% das respostas serão unificadas</li>
                    </ul>
                <?php else: ?>
                    <h3>🗑️ Limpar Respostas Automáticas</h3>
                    <p>Remove todas as respostas automáticas geradas anteriormente. As votações e usuários de exemplo permanecerão intactos.</p>
                    <ul class="vs-action-list">
                        <li>✓ Remove apenas respostas automáticas</li>
                        <li>✓ Preserva votações e usuários de exemplo</li>
                        <li>✓ Total de respostas: <?php echo $stats['respostas_automaticas']; ?></li>
                        <li>✓ Permite recriar novas respostas depois</li>
                    </ul>
                <?php endif; ?>
            </div>
            <?php if (!$stats['has_sample_responses']): ?>
                <button type="button" class="button button-primary vs-create-responses">
                    Criar Respostas Automáticas
                </button>
            <?php else: ?>
                <button type="button" class="button button-secondary vs-clear-responses">
                    Limpar Respostas
                </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Detalhes dos usuários de teste (se existirem) -->
    <?php if (!empty($stats['users_details'])): ?>
    <div class="vs-users-details">
        <h4>👥 Usuários de Teste Criados:</h4>
        <table class="vs-users-list">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Senha</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats['users_details'] as $user): ?>
                    <tr class="vs-user-item">
                        <td>
                            <?php echo esc_html($user['email']); ?>
                            <?php if ($user['created']): ?>
                                <span
                                    style="
                                        background-color: rgba(255, 107, 53, 0.5);
                                        color: #454545;
                                        padding: 0px 4px 1px 6px;
                                        border-radius: 20px;
                                        line-height: 1em;
                                        font-size: 11px;
                                        margin-left: 3px;
                                        margin-right: 3px;"
                                >
                                    Novo
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="vs-user-meta">
                                <code><?php echo esc_html($user['email']); ?></code>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>
    <?php endif; ?>

    <!-- Ações -->
    <div class="vs-actions">
        <?php if ($stats['has_sample_data']): ?>
            <!-- Dados existem - mostrar botão de limpeza -->
            <div class="vs-action-card vs-clear">
                <div class="vs-action-content">
                    <h3>🗑️ Limpar Dados de Exemplo</h3>
                    <p>Remove todos os dados de exemplo criados automaticamente.</p>
                    <ul class="vs-action-list">
                        <li>✓ <?php echo $stats['votacoes_exemplo']; ?> votações de exemplo</li>
                        <li>✓ <?php echo $stats['eventos_exemplo']; ?> eventos de exemplo</li>
                        <li>✓ <?php echo $stats['usuarios_exemplo']; ?> usuários de teste</li>
                    </ul>
                </div>
                <button type="button" class="button button-secondary vs-clear-data">
                    Limpar Dados de Exemplo
                </button>
            </div>
        <?php else: ?>
            <!-- Não há dados - mostrar botão de população -->
            <div class="vs-action-card vs-populate">
                <div class="vs-action-content">
                    <h3>🚀 Popular Dados de Exemplo</h3>
                    <p>Cria dados de exemplo para testar o sistema de votação.</p>
                    <ul class="vs-action-list">
                        <li>✓ 5 usuários de teste (um@teste.com.br, dois@teste.com.br, etc.)</li>
                        <li>✓ 4 eventos diferentes</li>
                        <li>✓ 5 votações com status variados</li>
                        <li>✓ 4 perguntas por votação (texto, select, radio, checkbox)</li>
                    </ul>
                </div>
                <button type="button" class="button button-primary vs-populate-data">
                    Popular Dados de Exemplo
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>
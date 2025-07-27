<?php
/**
 * Template para exibir votações encerradas do usuário
 * Usado pelo shortcode [votacoes_usuario_encerradas]
 */

defined('ABSPATH') || exit;
?>

<div class="vs-votacoes-usuario-encerradas">
    <h3>Suas Votações Encerradas</h3>
    
    <?php if (empty($votacoes_encerradas)): ?>
        <div class="vs-empty-state">
            <p>Você não possui votações encerradas.</p>
        </div>
    <?php else: ?>
        <div class="vs-votacoes-grid">
            <?php foreach ($votacoes_encerradas as $votacao): ?>
                <div class="card vs-votacao-card vs-votacao-encerrada">
                    <div class="vs-votacao-header">
                        <h4 class="vs-votacao-titulo">
                            <?php echo esc_html($votacao['titulo']); ?>
                        </h4>
                        <span class="vs-votacao-status vs-status-encerrada">Encerrada</span>
                    </div>
                    
                    <div class="vs-votacao-meta">
                        <p class="vs-votacao-data">
                            <strong>Data da votação:</strong> <?php echo esc_html($votacao['data']); ?>
                        </p>
                        <?php if ($votacao['data_resposta']): ?>
                            <p class="vs-resposta-data">
                                <strong>Respondido em:</strong> <?php echo esc_html(date('d/m/Y H:i', strtotime($votacao['data_resposta']))); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="vs-resumo-respostas">
                        <h5>Suas respostas:</h5>
                        <div class="vs-resumo-content">
                            <?php echo $votacao['resumo_respostas']; ?>
                        </div>
                    </div>
                    
                    <div class="vs-votacao-actions">
                        <a href="<?php echo esc_url($votacao['link']); ?>" class="button vs-btn-ver-respostas">
                            Ver Respostas
                        </a>
                        <!-- Votações encerradas não podem ser editadas -->
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.vs-votacoes-usuario-encerradas {
    margin: 20px 0;
}

.vs-votacoes-grid {
    display: grid;
    gap: 20px;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
}

.vs-votacao-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    background: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: box-shadow 0.3s ease;
}

.vs-votacao-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.vs-votacao-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.vs-votacao-titulo {
    margin: 0;
    font-size: 1.2em;
    flex: 1;
    color: #666;
}

.vs-votacao-status {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: bold;
}

.vs-status-encerrada {
    background: #f8d7da;
    color: #721c24;
}

.vs-votacao-meta {
    margin-bottom: 15px;
    font-size: 0.9em;
    color: #666;
}

.vs-votacao-meta p {
    margin: 5px 0;
}

.vs-resumo-respostas {
    margin-bottom: 20px;
}

.vs-resumo-respostas h5 {
    margin: 0 0 10px 0;
    font-size: 1em;
    color: #333;
}

.vs-resumo-content {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    font-size: 0.9em;
    line-height: 1.4;
}

.vs-votacao-actions {
    text-align: center;
}

.vs-btn-ver-respostas {
    background: #6c757d;
    color: white;
    padding: 10px 20px;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
    transition: background 0.3s ease;
    margin-bottom: 10px;
}

.vs-btn-ver-respostas:hover {
    background: #545b62;
    color: white;
}

.vs-btn-editar-link {
    display: block;
    color: #0073aa;
    text-decoration: none;
    font-size: 0.9em;
    transition: color 0.3s ease;
}

.vs-btn-editar-link:hover {
    color: #005a87;
    text-decoration: underline;
}

.vs-empty-state {
    text-align: center;
    padding: 40px 20px;
    background: #f8f9fa;
    border-radius: 8px;
    color: #666;
}
</style>
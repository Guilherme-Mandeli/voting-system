<?php
/**
 * Template para exibir votações disponíveis para o usuário
 * Usado pelo shortcode [votacoes_disponiveis]
 */

defined('ABSPATH') || exit;
?>

<div class="vs-votacoes-disponiveis">
    <h3>Votações Disponíveis</h3>
    
    <?php if (empty($votacoes_disponiveis)): ?>
        <div class="vs-empty-state">
            <p>Não há votações disponíveis no momento.</p>
        </div>
    <?php else: ?>
        <div class="vs-votacoes-grid">
            <?php foreach ($votacoes_disponiveis as $votacao): ?>
                <div class="card vs-votacao-card vs-votacao-disponivel">
                    <div class="vs-votacao-header">
                        <h4 class="vs-votacao-titulo">
                            <?php echo esc_html($votacao['titulo']); ?>
                        </h4>
                        <span class="vs-votacao-status vs-status-disponivel">Disponível</span>
                    </div>
                    
                    <?php if ($votacao['prazo']): ?>
                        <div class="vs-votacao-prazo">
                            <p><strong><?php echo esc_html($votacao['prazo']); ?></strong></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="vs-votacao-descricao">
                        <p><?php echo esc_html($votacao['descricao']); ?></p>
                    </div>
                    
                    <div class="vs-votacao-actions">
                        <a href="<?php echo esc_url($votacao['link']); ?>" class="button vs-btn-participar">
                            Participar
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.vs-votacoes-disponiveis {
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
    color: #333;
}

.vs-votacao-status {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
}

.vs-status-disponivel {
    background: #d1ecf1;
    color: #0c5460;
}

.vs-votacao-prazo {
    margin-bottom: 15px;
    padding: 10px;
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 4px;
    color: #856404;
}

.vs-votacao-prazo p {
    margin: 0;
    font-size: 0.9em;
}

.vs-votacao-descricao {
    margin-bottom: 20px;
    color: #666;
    line-height: 1.5;
}

.vs-votacao-descricao p {
    margin: 0;
}

.vs-votacao-actions {
    text-align: center;
}

.vs-btn-participar {
    background: #28a745;
    color: white;
    padding: 12px 24px;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
    font-weight: bold;
    transition: background 0.3s ease;
}

.vs-btn-participar:hover {
    background: #218838;
    color: white;
}

.vs-empty-state {
    text-align: center;
    padding: 40px 20px;
    background: #f8f9fa;
    border-radius: 8px;
    color: #666;
}
</style>
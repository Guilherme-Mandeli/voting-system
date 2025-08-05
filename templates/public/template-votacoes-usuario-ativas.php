<?php
/**
 * Template para exibir votações ativas do usuário
 * Usado pelo shortcode [votacoes_usuario_ativas]
 */

defined('ABSPATH') || exit;
?>

<div class="vs-votacoes-usuario-ativas">
    <h3>Suas Votações Ativas</h3>
    
    <?php if (empty($votacoes_ativas)): ?>
        <div class="vs-empty-state">
            <p>Você não possui votações ativas no momento.</p>
        </div>
    <?php else: ?>
        <div class="vs-votacoes-grid">
            <?php foreach ($votacoes_ativas as $votacao): ?>
                <div class="card vs-votacao-card vs-votacao-ativa">
                    <div class="vs-votacao-header">
                        <h4 class="vs-votacao-titulo">
                            <a href="<?php echo esc_url($votacao['link']); ?>">
                                <?php echo esc_html($votacao['titulo']); ?>
                            </a>
                        </h4>
                        <span class="vs-votacao-status vs-status-ativa">Ativa</span>
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
                        <h5>Resumo das suas respostas:</h5>
                        <div class="vs-resumo-content">
                            <?php echo wp_kses_post($votacao['resumo_respostas']); ?>
                        </div>
                    </div>
                    
                    <div class="vs-votacao-actions">
                        <a href="<?php echo esc_url($votacao['link']); ?>" class="button vs-btn-ver-respostas">
                            Ver Respostas
                        </a>
                        
                        <?php if ($votacao['permitir_edicao']): ?>
                            <a href="<?php echo esc_url($votacao['link']); ?>" class="vs-btn-editar-link">
                                Editar voto
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.vs-votacoes-usuario-ativas {
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
}

.vs-votacao-titulo a {
    text-decoration: none;
    color: #333;
}

.vs-votacao-titulo a:hover {
    color: #0073aa;
}

.vs-votacao-status {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: bold;
}

.vs-status-ativa {
    background: #d4edda;
    color: #155724;
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
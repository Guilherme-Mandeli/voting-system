<?php
// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Template para exibir feed filtrado das votações
 * 
 * @param array $votacoes Array com as votações encontradas
 */
?>

<?php if (empty($votacoes)): ?>
    <p>Nenhuma votação encontrada com os filtros aplicados.</p>
<?php else: ?>
    <ul class="vs-feed-votacoes">
        <?php foreach ($votacoes as $votacao): ?>
            <li>
                <a href="<?php echo esc_url($votacao['link']); ?>"><?php echo esc_html($votacao['titulo']); ?></a>
                — Ano: <?php echo esc_html($votacao['ano']); ?>
                — Status: <?php echo esc_html($votacao['status']); ?>
                — Código: <?php echo esc_html($votacao['codigo']); ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
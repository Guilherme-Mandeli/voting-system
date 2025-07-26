<?php
// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Template para o formulário de votação
 * 
 * @param array $perguntas Array com as perguntas da votação
 * @param int $votacao_id ID da votação
 * @param array $respostas Array com as respostas do usuário (se existirem)
 * @param bool $ja_votou Se o usuário já votou
 * @param bool $permitir_edicao Se a votação permite edição
 * @param string $encerrada Status da votação
 */
?>

<?php if ($ja_votou && $permitir_edicao !== '1'): ?>
    <div class="vs-votacao-ja-respondida p-4 border rounded bg-gray-50 shadow-sm">
        <p><strong>Você já respondeu esta votação. Suas respostas foram:</strong></p>
        <?php echo vs_render_respostas_votacao($perguntas, $respostas); ?>
    </div>
<?php else: ?>
    <?php $encerrada = get_post_meta($votacao_id, '_vs_status', true); ?>

    <?php if ($ja_votou): ?>
        <div class="vs-votacao-container p-4 border rounded bg-gray-50 shadow-sm">
            <?php if ($permitir_edicao === '1' && $encerrada !== 'encerrada'): ?>
                <p class="font-semibold mb-4">Você já respondeu esta votação. Você pode editar seu voto abaixo.</p>
                <div id="vs-modo-leitura">
                    <?php echo vs_render_respostas_votacao($perguntas, $respostas); ?>
                    <button id="vs-btn-editar" class="vs-button bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-full transition shadow">Editar voto</button>
                </div>
                <div id="vs-modo-edicao" style="display:none;">
                    <?php echo vs_render_formulario_votacao($perguntas, $votacao_id, $respostas); ?>
                    <button id="vs-btn-cancelar" class="vs-button bg-white hover:bg-gray-100 text-gray-800 font-semibold py-2 px-4 border border-gray-400 rounded shadow">Cancelar</button>
                </div>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const btnEditar = document.getElementById('vs-btn-editar');
                    const btnCancelar = document.getElementById('vs-btn-cancelar');
                    const modoLeitura = document.getElementById('vs-modo-leitura');
                    const modoEdicao = document.getElementById('vs-modo-edicao');

                    btnEditar.addEventListener('click', function() {
                        modoLeitura.style.display = 'none';
                        modoEdicao.style.display = 'block';
                    });

                    btnCancelar.addEventListener('click', function() {
                        modoEdicao.style.display = 'none';
                        modoLeitura.style.display = 'block';
                    });
                });
                </script>
            <?php else: ?>
                <p class="font-semibold mb-4">Você já respondeu esta votação. Suas respostas foram:</p>
                <?php echo vs_render_respostas_votacao($perguntas, $respostas); ?>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="vs-votacao-container p-4 border rounded bg-gray-50 shadow-sm">
            <?php echo vs_render_formulario_votacao($perguntas, $votacao_id, $respostas); ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

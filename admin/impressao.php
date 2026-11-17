<?php
// Etapa 5: calculadora do preço de impressão (páginas x preço unitário do serviço).
// O total aparece na hora pelo JavaScript e também é calculado aqui no servidor.
require_once __DIR__ . '/../includes/auth.php';
exigir_login();

$titulo_pagina = 'Preço de impressão';
$raiz = '../';

$servicos = $pdo->query("SELECT id, nome, preco FROM produtos WHERE tipo = 'servico' ORDER BY nome")->fetchAll();

$servico_id    = (int) ($_GET['servico'] ?? 0);
$paginas_texto = trim($_GET['paginas'] ?? '');
$servico_escolhido = null;
$paginas = null;
$total   = null;
$erro    = '';

// Só calcula quando o formulário foi enviado
if (isset($_GET['servico']) || isset($_GET['paginas'])) {
    foreach ($servicos as $s) {
        if ((int) $s['id'] === $servico_id) {
            $servico_escolhido = $s;
        }
    }
    $paginas = para_inteiro($paginas_texto, 1, 100000);

    if ($servico_escolhido === null) {
        $erro = 'Escolha o tipo de serviço.';
    } elseif ($paginas === null) {
        $erro = 'Informe o número de páginas (número inteiro, de 1 a 100000).';
    } else {
        // Conta feita em centavos para não ter erro de arredondamento
        $centavos = (int) round($servico_escolhido['preco'] * 100);
        $total = $centavos * $paginas / 100;
    }
}

include __DIR__ . '/../includes/header.php';
mostrar_mensagem();
?>

<section class="cartao">
    <?php if (!$servicos): ?>
        <p>Nenhum serviço cadastrado ainda. O gerente pode cadastrar na página Estoque.</p>
    <?php else: ?>
        <?php if ($erro !== ''): ?>
            <div class="aviso aviso-erro" role="alert"><strong>Erro:</strong> <?= e($erro) ?></div>
        <?php endif; ?>

        <form class="formulario" method="get" action="impressao.php" data-calculo>
            <label for="servico">Tipo de serviço</label>
            <select id="servico" name="servico" data-calc-item required>
                <option value="">Escolha...</option>
                <?php foreach ($servicos as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" data-preco="<?= e(number_format((float) $s['preco'], 2, '.', '')) ?>"<?= (int) $s['id'] === $servico_id ? ' selected' : '' ?>>
                        <?= e($s['nome']) ?> – <?= e(formatar_dinheiro($s['preco'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="paginas">Número de páginas (ou unidades)</label>
            <input type="number" id="paginas" name="paginas" value="<?= e($paginas_texto) ?>" min="1" max="100000" step="1" inputmode="numeric" data-calc-qtd required>

            <div class="resultado" aria-live="polite">
                <span>Total</span>
                <strong class="total-destaque" data-calc-total><?= $total !== null ? e(formatar_dinheiro($total)) : 'R$ 0,00' ?></strong>
            </div>

            <div class="botoes">
                <button type="submit" class="botao">Calcular</button>
                <a class="botao botao-secundario" data-calc-link data-base="vendas.php"
                   href="<?= $total !== null ? 'vendas.php?produto=' . (int) $servico_escolhido['id'] . '&amp;quantidade=' . (int) $paginas . '#nova-venda' : '#' ?>"
                   <?= $total === null ? 'hidden' : '' ?>>Registrar como venda</a>
            </div>
        </form>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

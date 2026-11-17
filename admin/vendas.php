<?php
// Etapa 5: registro de vendas e histórico.
// Cada venda é um item (produto ou serviço) com a quantidade vendida.
// Se for material, o estoque diminui automaticamente.
require_once __DIR__ . '/../includes/auth.php';
exigir_login();

$titulo_pagina = 'Vendas';
$raiz = '../';
$erros = [];

// Valores do formulário (podem vir da calculadora de impressão pelo endereço)
$venda = ['produto_id' => (int) ($_GET['produto'] ?? 0), 'quantidade' => trim($_GET['quantidade'] ?? '')];

// ---------- Registrar venda ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'registrar') {
    $venda['produto_id'] = (int) ($_POST['produto_id'] ?? 0);
    $venda['quantidade'] = trim($_POST['quantidade'] ?? '');
    $qtd = para_inteiro($venda['quantidade'], 1, 100000);

    if ($venda['produto_id'] <= 0) {
        $erros[] = 'Escolha o produto ou serviço.';
    }
    if ($qtd === null) {
        $erros[] = 'A quantidade deve ser um número inteiro maior que zero.';
    }

    if (!$erros) {
        $pdo->beginTransaction();
        try {
            // FOR UPDATE trava a linha enquanto a venda é gravada
            $stmt = $pdo->prepare('SELECT id, nome, tipo, preco, quantidade FROM produtos WHERE id = ? FOR UPDATE');
            $stmt->execute([$venda['produto_id']]);
            $item = $stmt->fetch();

            if (!$item) {
                $pdo->rollBack();
                $erros[] = 'Produto não encontrado.';
            } elseif ($item['tipo'] === 'material' && $qtd > $item['quantidade']) {
                $pdo->rollBack();
                $erros[] = 'Estoque insuficiente: há apenas ' . $item['quantidade'] . ' de ' . $item['nome'] . '.';
            } else {
                // O valor é sempre calculado aqui no servidor, com o preço cadastrado
                $total = ((int) round($item['preco'] * 100)) * $qtd / 100;

                if ($total > 99999999.99) {
                    $pdo->rollBack();
                    $erros[] = 'O valor total ficou grande demais. Diminua a quantidade.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO vendas (produto_id, quantidade, valor_total, usuario_id) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$item['id'], $qtd, number_format($total, 2, '.', ''), $_SESSION['usuario_id']]);

                    // Material: diminui o estoque e registra a saída no histórico do estoque
                    if ($item['tipo'] === 'material') {
                        $stmt = $pdo->prepare('UPDATE produtos SET quantidade = ? WHERE id = ?');
                        $stmt->execute([$item['quantidade'] - $qtd, $item['id']]);

                        $stmt = $pdo->prepare("INSERT INTO movimentacoes (produto_id, tipo, quantidade, usuario_id) VALUES (?, 'saida', ?, ?)");
                        $stmt->execute([$item['id'], $qtd, $_SESSION['usuario_id']]);
                    }

                    $pdo->commit();

                    definir_mensagem('sucesso', 'Venda registrada: ' . $qtd . ' de ' . $item['nome'] . ', total ' . formatar_dinheiro($total) . '.');
                    header('Location: vendas.php');
                    exit;
                }
            }
        } catch (Exception $erro) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $erro;
        }
    }
}

// ---------- Produtos e serviços para escolher na venda ----------
$lista_servicos  = [];
$lista_materiais = [];
foreach ($pdo->query('SELECT id, nome, tipo, preco, quantidade FROM produtos ORDER BY nome')->fetchAll() as $p) {
    if ($p['tipo'] === 'servico') {
        $lista_servicos[] = $p;
    } else {
        $lista_materiais[] = $p;
    }
}

// ---------- Histórico, com filtro opcional por período ----------
$de  = data_valida($_GET['de'] ?? '');
$ate = data_valida($_GET['ate'] ?? '');

$condicoes = [];
$params    = [];
if ($de !== '') {
    $condicoes[] = 'v.data_venda >= ?';
    $params[]    = $de . ' 00:00:00';
}
if ($ate !== '') {
    $condicoes[] = 'v.data_venda <= ?';
    $params[]    = $ate . ' 23:59:59';
}
$onde = $condicoes ? ' WHERE ' . implode(' AND ', $condicoes) : '';

$stmt = $pdo->prepare('SELECT COUNT(*) AS quantidade, COALESCE(SUM(v.valor_total), 0) AS soma FROM vendas v' . $onde);
$stmt->execute($params);
$resumo = $stmt->fetch();

$stmt = $pdo->prepare(
    'SELECT v.data_venda, p.nome AS produto, v.quantidade, v.valor_total, u.nome AS funcionario
       FROM vendas v
       JOIN produtos p ON p.id = v.produto_id
       JOIN usuarios u ON u.id = v.usuario_id'
    . $onde . ' ORDER BY v.id DESC LIMIT 100'
);
$stmt->execute($params);
$vendas = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
mostrar_mensagem();
?>

<section class="cartao" id="nova-venda">
    <h2>Nova venda</h2>

    <?php if ($erros): ?>
        <div class="aviso aviso-erro" role="alert">
            <strong>Corrija antes de registrar:</strong>
            <ul>
                <?php foreach ($erros as $erro): ?>
                    <li><?= e($erro) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form class="formulario" method="post" action="vendas.php#nova-venda" data-calculo>
        <input type="hidden" name="acao" value="registrar">

        <label for="produto_id">Produto ou serviço</label>
        <select id="produto_id" name="produto_id" data-calc-item required>
            <option value="">Escolha...</option>

            <?php if ($lista_servicos): ?>
                <optgroup label="Serviços">
                    <?php foreach ($lista_servicos as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" data-preco="<?= e(number_format((float) $s['preco'], 2, '.', '')) ?>"<?= (int) $s['id'] === $venda['produto_id'] ? ' selected' : '' ?>>
                            <?= e($s['nome']) ?> – <?= e(formatar_dinheiro($s['preco'])) ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endif; ?>

            <?php if ($lista_materiais): ?>
                <optgroup label="Materiais">
                    <?php foreach ($lista_materiais as $m): ?>
                        <option value="<?= (int) $m['id'] ?>" data-preco="<?= e(number_format((float) $m['preco'], 2, '.', '')) ?>"<?= (int) $m['id'] === $venda['produto_id'] ? ' selected' : '' ?><?= $m['quantidade'] <= 0 ? ' disabled' : '' ?>>
                            <?= e($m['nome']) ?> – <?= e(formatar_dinheiro($m['preco'])) ?> (<?= $m['quantidade'] > 0 ? 'em estoque: ' . (int) $m['quantidade'] : 'sem estoque' ?>)
                        </option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endif; ?>
        </select>

        <label for="quantidade">Quantidade</label>
        <input type="number" id="quantidade" name="quantidade" value="<?= e($venda['quantidade']) ?>" min="1" max="100000" step="1" inputmode="numeric" data-calc-qtd required>

        <div class="resultado" aria-live="polite">
            <span>Total</span>
            <strong class="total-destaque" data-calc-total>R$ 0,00</strong>
        </div>

        <div class="botoes">
            <button type="submit" class="botao">Registrar venda</button>
        </div>
    </form>
</section>

<section class="cartao" id="historico">
    <h2>Histórico de vendas</h2>

    <form class="formulario" method="get" action="vendas.php#historico">
        <div class="linha-campos">
            <div>
                <label for="de">De</label>
                <input type="date" id="de" name="de" value="<?= e($de) ?>">
            </div>
            <div>
                <label for="ate">Até</label>
                <input type="date" id="ate" name="ate" value="<?= e($ate) ?>">
            </div>
        </div>
        <div class="botoes">
            <button type="submit" class="botao botao-pequeno">Filtrar</button>
            <?php if ($de !== '' || $ate !== ''): ?>
                <a href="vendas.php#historico" class="botao botao-secundario botao-pequeno">Limpar filtro</a>
            <?php endif; ?>
        </div>
    </form>

    <p class="resumo">
        <strong><?= (int) $resumo['quantidade'] ?></strong> venda(s)<?= ($de !== '' || $ate !== '') ? ' no período' : '' ?>,
        total <strong><?= e(formatar_dinheiro($resumo['soma'])) ?></strong>.
        <?php if ($resumo['quantidade'] > 100): ?>
            Mostrando as 100 mais recentes.
        <?php endif; ?>
    </p>

    <?php if (!$vendas): ?>
        <p>Nenhuma venda encontrada.</p>
    <?php else: ?>
        <div class="tabela-rolagem">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Produto ou serviço</th>
                        <th class="numero">Quantidade</th>
                        <th class="numero">Total</th>
                        <th>Funcionário</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendas as $v): ?>
                        <tr>
                            <td><?= e(formatar_data_hora($v['data_venda'])) ?></td>
                            <td><?= e($v['produto']) ?></td>
                            <td class="numero"><?= (int) $v['quantidade'] ?></td>
                            <td class="numero"><?= e(formatar_dinheiro($v['valor_total'])) ?></td>
                            <td><?= e($v['funcionario']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

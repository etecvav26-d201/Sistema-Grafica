<?php
// Etapa 6: gastos da gráfica (compras de materiais e outros custos). Somente o gerente.
require_once __DIR__ . '/../includes/auth.php';
exigir_gerente();

$titulo_pagina = 'Gastos';
$raiz = '../';
$erros = [];

// Valores do formulário (a data começa com o dia de hoje)
$gasto = ['descricao' => '', 'valor' => '', 'data_gasto' => date('Y-m-d')];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // ----- Cadastrar gasto -----
    if ($acao === 'salvar') {
        $gasto['descricao']  = trim($_POST['descricao'] ?? '');
        $gasto['valor']      = trim($_POST['valor'] ?? '');
        $gasto['data_gasto'] = trim($_POST['data_gasto'] ?? '');

        $valor = para_decimal($gasto['valor']);

        if ($gasto['descricao'] === '' || mb_strlen($gasto['descricao']) > 150) {
            $erros[] = 'Informe a descrição (até 150 letras).';
        }
        if ($valor === null || $valor <= 0 || $valor > 99999999.99) {
            $erros[] = 'Informe um valor válido maior que zero, como 45,90.';
        }
        if (data_valida($gasto['data_gasto']) === '') {
            $erros[] = 'Informe uma data válida.';
        }

        if (!$erros) {
            $stmt = $pdo->prepare('INSERT INTO gastos (descricao, valor, data_gasto) VALUES (?, ?, ?)');
            $stmt->execute([$gasto['descricao'], number_format($valor, 2, '.', ''), $gasto['data_gasto']]);
            definir_mensagem('sucesso', 'Gasto registrado: ' . $gasto['descricao'] . ', ' . formatar_dinheiro($valor) . '.');
            header('Location: gastos.php');
            exit;
        }
    }

    // ----- Excluir gasto (para corrigir um lançamento errado) -----
    elseif ($acao === 'excluir') {
        $stmt = $pdo->prepare('DELETE FROM gastos WHERE id = ?');
        $stmt->execute([(int) ($_POST['id'] ?? 0)]);
        definir_mensagem('sucesso', 'Gasto excluído.');
        header('Location: gastos.php');
        exit;
    }
}

// ---------- Lista, com filtro opcional por período ----------
$de  = data_valida($_GET['de'] ?? '');
$ate = data_valida($_GET['ate'] ?? '');

$condicoes = [];
$params    = [];
if ($de !== '') {
    $condicoes[] = 'data_gasto >= ?';
    $params[]    = $de;
}
if ($ate !== '') {
    $condicoes[] = 'data_gasto <= ?';
    $params[]    = $ate;
}
$onde = $condicoes ? ' WHERE ' . implode(' AND ', $condicoes) : '';

$stmt = $pdo->prepare('SELECT COUNT(*) AS quantidade, COALESCE(SUM(valor), 0) AS soma FROM gastos' . $onde);
$stmt->execute($params);
$resumo = $stmt->fetch();

$stmt = $pdo->prepare('SELECT id, descricao, valor, data_gasto FROM gastos' . $onde . ' ORDER BY data_gasto DESC, id DESC LIMIT 200');
$stmt->execute($params);
$gastos = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
mostrar_mensagem();
?>

<section class="cartao">
    <h2>Novo gasto</h2>

    <?php if ($erros): ?>
        <div class="aviso aviso-erro" role="alert">
            <strong>Corrija antes de salvar:</strong>
            <ul>
                <?php foreach ($erros as $erro): ?>
                    <li><?= e($erro) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form class="formulario" method="post" action="gastos.php">
        <input type="hidden" name="acao" value="salvar">

        <label for="descricao">Descrição (ex.: compra de papel, conta de luz)</label>
        <input type="text" id="descricao" name="descricao" value="<?= e($gasto['descricao']) ?>" maxlength="150" required>

        <label for="valor">Valor (use vírgula, ex.: 45,90)</label>
        <input type="text" id="valor" name="valor" value="<?= e($gasto['valor']) ?>" inputmode="decimal" placeholder="0,00" required>

        <label for="data_gasto">Data do gasto</label>
        <input type="date" id="data_gasto" name="data_gasto" value="<?= e($gasto['data_gasto']) ?>" required>

        <div class="botoes">
            <button type="submit" class="botao">Registrar gasto</button>
        </div>
    </form>
</section>

<section class="cartao">
    <h2>Gastos registrados</h2>

    <form class="formulario" method="get" action="gastos.php">
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
                <a href="gastos.php" class="botao botao-secundario botao-pequeno">Limpar filtro</a>
            <?php endif; ?>
        </div>
    </form>

    <p class="resumo">
        <strong><?= (int) $resumo['quantidade'] ?></strong> gasto(s)<?= ($de !== '' || $ate !== '') ? ' no período' : '' ?>,
        total <strong><?= e(formatar_dinheiro($resumo['soma'])) ?></strong>.
        <?php if ($resumo['quantidade'] > 200): ?>
            Mostrando os 200 mais recentes.
        <?php endif; ?>
    </p>

    <?php if (!$gastos): ?>
        <p>Nenhum gasto encontrado.</p>
    <?php else: ?>
        <div class="tabela-rolagem">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th class="numero">Valor</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gastos as $g): ?>
                        <tr>
                            <td><?= e(formatar_data($g['data_gasto'])) ?></td>
                            <td><?= e($g['descricao']) ?></td>
                            <td class="numero"><?= e(formatar_dinheiro($g['valor'])) ?></td>
                            <td>
                                <form method="post" action="gastos.php" onsubmit="return confirm('Excluir este gasto?');">
                                    <input type="hidden" name="acao" value="excluir">
                                    <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                                    <button type="submit" class="botao botao-perigo botao-pequeno">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

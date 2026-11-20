<?php
// Monta os dados e o texto do relatório (usado na prévia da página e no e-mail).
require_once __DIR__ . '/funcoes.php';

// Busca no banco tudo o que o relatório mostra. $de e $ate são datas AAAA-MM-DD já validadas.
function montar_relatorio($pdo, $de, $ate) {
    $inicio = $de . ' 00:00:00';
    $fim    = $ate . ' 23:59:59';

    $stmt = $pdo->prepare('SELECT COUNT(*) AS qtd, COALESCE(SUM(valor_total), 0) AS total FROM vendas WHERE data_venda BETWEEN ? AND ?');
    $stmt->execute([$inicio, $fim]);
    $vendas = $stmt->fetch();

    $stmt = $pdo->prepare(
        'SELECT p.nome, SUM(v.quantidade) AS quantidade, SUM(v.valor_total) AS total
           FROM vendas v
           JOIN produtos p ON p.id = v.produto_id
          WHERE v.data_venda BETWEEN ? AND ?
          GROUP BY p.id, p.nome
          ORDER BY total DESC'
    );
    $stmt->execute([$inicio, $fim]);
    $vendas_por_item = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT descricao, valor, data_gasto FROM gastos WHERE data_gasto BETWEEN ? AND ? ORDER BY data_gasto, id');
    $stmt->execute([$de, $ate]);
    $gastos = $stmt->fetchAll();

    $gastos_total = 0;
    foreach ($gastos as $g) {
        $gastos_total += (float) $g['valor'];
    }

    $estoque = $pdo->query("SELECT nome, quantidade, estoque_minimo FROM produtos WHERE tipo = 'material' ORDER BY nome")->fetchAll();
    $qtd_baixos = 0;
    foreach ($estoque as $m) {
        if ($m['quantidade'] <= $m['estoque_minimo']) {
            $qtd_baixos++;
        }
    }

    return [
        'de'              => $de,
        'ate'             => $ate,
        'vendas_qtd'      => (int) $vendas['qtd'],
        'vendas_total'    => (float) $vendas['total'],
        'vendas_por_item' => $vendas_por_item,
        'gastos'          => $gastos,
        'gastos_total'    => round($gastos_total, 2),
        'estoque'         => $estoque,
        'qtd_baixos'      => $qtd_baixos,
    ];
}

// Relatório em HTML. Usa estilos "inline" porque os programas de e-mail ignoram arquivos CSS.
function relatorio_html($d) {
    $th  = 'style="text-align:left;padding:6px 8px;background:#eef1f5;border-bottom:1px solid #d9dde3;"';
    $thn = 'style="text-align:right;padding:6px 8px;background:#eef1f5;border-bottom:1px solid #d9dde3;"';
    $td  = 'style="padding:6px 8px;border-bottom:1px solid #e6e9ee;"';
    $tdn = 'style="padding:6px 8px;border-bottom:1px solid #e6e9ee;text-align:right;"';
    $h3  = 'style="margin:24px 0 8px;font-size:16px;"';

    $saldo = round($d['vendas_total'] - $d['gastos_total'], 2);

    ob_start();
    ?>
    <div style="font-family:Arial,Helvetica,sans-serif;color:#1e2430;max-width:640px;line-height:1.4;">
        <h2 style="margin:0 0 4px;"><?= e(NOME_SISTEMA) ?> – Relatório</h2>
        <p style="margin:0 0 16px;color:#5b6472;">
            Período: <?= e(formatar_data($d['de'])) ?> a <?= e(formatar_data($d['ate'])) ?>
        </p>

        <table style="border-collapse:collapse;width:100%;">
            <tr>
                <td <?= $td ?>>Vendas (<?= (int) $d['vendas_qtd'] ?>)</td>
                <td <?= $tdn ?>><strong><?= e(formatar_dinheiro($d['vendas_total'])) ?></strong></td>
            </tr>
            <tr>
                <td <?= $td ?>>Gastos</td>
                <td <?= $tdn ?>><strong><?= e(formatar_dinheiro($d['gastos_total'])) ?></strong></td>
            </tr>
            <tr>
                <td <?= $td ?>>Vendas menos gastos</td>
                <td <?= $tdn ?>><strong><?= e(formatar_dinheiro($saldo)) ?></strong></td>
            </tr>
        </table>

        <h3 <?= $h3 ?>>Vendas por produto ou serviço</h3>
        <?php if (!$d['vendas_por_item']): ?>
            <p>Nenhuma venda no período.</p>
        <?php else: ?>
            <table style="border-collapse:collapse;width:100%;">
                <tr><th <?= $th ?>>Produto ou serviço</th><th <?= $thn ?>>Quantidade</th><th <?= $thn ?>>Total</th></tr>
                <?php foreach ($d['vendas_por_item'] as $v): ?>
                    <tr>
                        <td <?= $td ?>><?= e($v['nome']) ?></td>
                        <td <?= $tdn ?>><?= (int) $v['quantidade'] ?></td>
                        <td <?= $tdn ?>><?= e(formatar_dinheiro($v['total'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <h3 <?= $h3 ?>>Gastos</h3>
        <?php if (!$d['gastos']): ?>
            <p>Nenhum gasto no período.</p>
        <?php else: ?>
            <table style="border-collapse:collapse;width:100%;">
                <tr><th <?= $th ?>>Data</th><th <?= $th ?>>Descrição</th><th <?= $thn ?>>Valor</th></tr>
                <?php foreach ($d['gastos'] as $g): ?>
                    <tr>
                        <td <?= $td ?>><?= e(formatar_data($g['data_gasto'])) ?></td>
                        <td <?= $td ?>><?= e($g['descricao']) ?></td>
                        <td <?= $tdn ?>><?= e(formatar_dinheiro($g['valor'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <h3 <?= $h3 ?>>Estoque atual de materiais</h3>
        <?php if (!$d['estoque']): ?>
            <p>Nenhum material cadastrado.</p>
        <?php else: ?>
            <p style="margin:0 0 8px;">Materiais com estoque baixo ou zerado: <strong><?= (int) $d['qtd_baixos'] ?></strong></p>
            <table style="border-collapse:collapse;width:100%;">
                <tr><th <?= $th ?>>Material</th><th <?= $thn ?>>Em estoque</th><th <?= $thn ?>>Mínimo</th><th <?= $th ?>>Situação</th></tr>
                <?php foreach ($d['estoque'] as $m): ?>
                    <?php [$texto_situacao] = situacao_estoque($m['quantidade'], $m['estoque_minimo']); ?>
                    <tr>
                        <td <?= $td ?>><?= e($m['nome']) ?></td>
                        <td <?= $tdn ?>><?= (int) $m['quantidade'] ?></td>
                        <td <?= $tdn ?>><?= (int) $m['estoque_minimo'] ?></td>
                        <td <?= $td ?>><?= e($texto_situacao) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// Versão em texto simples, usada no corpo do e-mail (mailto não aceita HTML).
// Com $detalhado = true inclui as principais vendas e os materiais que precisam de reposição.
function relatorio_texto($d, $detalhado = false) {
    $saldo = round($d['vendas_total'] - $d['gastos_total'], 2);

    $linhas = [
        'Relatório - ' . NOME_SISTEMA,
        'Período: ' . formatar_data($d['de']) . ' a ' . formatar_data($d['ate']),
        '',
        'Vendas (' . $d['vendas_qtd'] . '): ' . formatar_dinheiro($d['vendas_total']),
        'Gastos: ' . formatar_dinheiro($d['gastos_total']),
        'Vendas menos gastos: ' . formatar_dinheiro($saldo),
    ];

    if ($detalhado && $d['vendas_por_item']) {
        $linhas[] = '';
        $linhas[] = 'Principais vendas no período:';
        foreach (array_slice($d['vendas_por_item'], 0, 5) as $v) {
            $linhas[] = '- ' . $v['nome'] . ': ' . (int) $v['quantidade'] . ' (' . formatar_dinheiro($v['total']) . ')';
        }
    }

    $linhas[] = '';
    $linhas[] = 'Materiais com estoque baixo ou zerado: ' . $d['qtd_baixos'];

    if ($detalhado && $d['qtd_baixos'] > 0) {
        $nomes = [];
        foreach ($d['estoque'] as $m) {
            if ($m['quantidade'] <= $m['estoque_minimo']) {
                $nomes[] = $m['nome'];
            }
        }
        $linhas[] = implode(', ', array_slice($nomes, 0, 8));
    }

    return implode("\r\n", $linhas);
}

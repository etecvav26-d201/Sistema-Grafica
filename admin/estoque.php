<?php
// Todo usuário logado consulta e movimenta o estoque.
// Só o gerente cadastra e edita produtos e preços.

require_once __DIR__ . '/../includes/auth.php';
exigir_login();

$titulo_pagina = 'Estoque';
$raiz = '../';
$eh_gerente = ($_SESSION['nivel'] === 'gerente');

$erros_mov  = [];
$erros_prod = [];

// Valores que aparecem nos formulários
$mov  = ['produto_id' => (int) ($_GET['produto'] ?? 0), 'tipo' => 'entrada', 'quantidade' => ''];
$prod = ['id' => 0, 'nome' => '', 'tipo' => 'material', 'preco' => '', 'quantidade' => '0', 'estoque_minimo' => '0'];

// Quando um formulário é enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // Entrada ou saída de estoque
    if ($acao === 'movimentar') {
        $mov['produto_id'] = (int) ($_POST['produto_id'] ?? 0);
        $mov['tipo']       = $_POST['tipo'] ?? '';
        $mov['quantidade'] = trim($_POST['quantidade'] ?? '');
        $qtd = para_inteiro($mov['quantidade'], 1, 1000000);

        if ($mov['produto_id'] <= 0) {
            $erros_mov[] = 'Escolha o material.';
        }
        if (!in_array($mov['tipo'], ['entrada', 'saida'], true)) {
            $erros_mov[] = 'Escolha entrada ou saída.';
        }
        if ($qtd === null) {
            $erros_mov[] = 'A quantidade deve ser um número inteiro maior que zero.';
        }

        if (!$erros_mov) {
            $pdo->beginTransaction();
            try {
                // FOR UPDATE trava a linha, para duas pessoas não mexerem no mesmo item ao mesmo tempo
                $stmt = $pdo->prepare("SELECT nome, quantidade FROM produtos WHERE id = ? AND tipo = 'material' FOR UPDATE");
                $stmt->execute([$mov['produto_id']]);
                $item = $stmt->fetch();

                if (!$item) {
                    $pdo->rollBack();
                    $erros_mov[] = 'Material não encontrado.';
                } elseif ($mov['tipo'] === 'saida' && $qtd > $item['quantidade']) {
                    $pdo->rollBack();
                    $erros_mov[] = 'Saída maior que o estoque: há apenas ' . $item['quantidade'] . ' em estoque.';
                } else {
                    $nova = ($mov['tipo'] === 'entrada') ? $item['quantidade'] + $qtd : $item['quantidade'] - $qtd;

                    $stmt = $pdo->prepare('UPDATE produtos SET quantidade = ? WHERE id = ?');
                    $stmt->execute([$nova, $mov['produto_id']]);

                    $stmt = $pdo->prepare('INSERT INTO movimentacoes (produto_id, tipo, quantidade, usuario_id) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$mov['produto_id'], $mov['tipo'], $qtd, $_SESSION['usuario_id']]);

                    $pdo->commit();

                    definir_mensagem('sucesso', ($mov['tipo'] === 'entrada' ? 'Entrada' : 'Saída')
                        . ' registrada: ' . $qtd . ' de ' . $item['nome'] . '. Estoque agora: ' . $nova . '.');
                    header('Location: estoque.php');
                    exit;
                }
            } catch (Exception $erro) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $erro;
            }
        }
    }

    // Cadastrar ou editar produto (somente o gerente)
    elseif ($acao === 'salvar_produto') {
        if (!$eh_gerente) {
            definir_mensagem('erro', 'Só o gerente pode cadastrar ou editar produtos.');
            header('Location: estoque.php');
            exit;
        }

        $prod['id']             = (int) ($_POST['id'] ?? 0);
        $prod['nome']           = trim($_POST['nome'] ?? '');
        $prod['tipo']           = $_POST['tipo'] ?? 'material';
        $prod['preco']          = trim($_POST['preco'] ?? '');
        $prod['quantidade']     = trim($_POST['quantidade'] ?? '0');
        $prod['estoque_minimo'] = trim($_POST['estoque_minimo'] ?? '0');

        // Ao editar, o tipo não muda: vem do banco
        if ($prod['id'] > 0) {
            $stmt = $pdo->prepare('SELECT tipo FROM produtos WHERE id = ?');
            $stmt->execute([$prod['id']]);
            $tipo_banco = $stmt->fetchColumn();
            if ($tipo_banco === false) {
                $erros_prod[] = 'Produto não encontrado.';
            } else {
                $prod['tipo'] = $tipo_banco;
            }
        }

        if (!in_array($prod['tipo'], ['material', 'servico'], true)) {
            $erros_prod[] = 'Escolha um tipo válido.';
        }
        if ($prod['nome'] === '' || mb_strlen($prod['nome']) > 100) {
            $erros_prod[] = 'Informe o nome (até 100 letras).';
        }

        $preco = para_decimal($prod['preco']);
        if ($preco === null || $preco < 0 || $preco > 99999999.99) {
            $erros_prod[] = 'Informe um preço válido, como 12,90.';
        }

        // Serviço não controla estoque: quantidade e mínimo ficam em zero
        $eh_material = ($prod['tipo'] === 'material');
        $minimo  = $eh_material ? para_inteiro($prod['estoque_minimo'], 0, 1000000) : 0;
        $inicial = ($eh_material && $prod['id'] === 0) ? para_inteiro($prod['quantidade'], 0, 1000000) : 0;
        if ($minimo === null) {
            $erros_prod[] = 'O estoque mínimo deve ser um número inteiro (0 ou mais).';
        }
        if ($inicial === null) {
            $erros_prod[] = 'A quantidade inicial deve ser um número inteiro (0 ou mais).';
        }

        // O nome não pode se repetir
        if (!$erros_prod) {
            $stmt = $pdo->prepare('SELECT id FROM produtos WHERE nome = ? AND id <> ?');
            $stmt->execute([$prod['nome'], $prod['id']]);
            if ($stmt->fetch()) {
                $erros_prod[] = 'Já existe um produto com esse nome.';
            }
        }

        if (!$erros_prod) {
            if ($prod['id'] === 0) {
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare('INSERT INTO produtos (nome, tipo, preco, quantidade, estoque_minimo) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([$prod['nome'], $prod['tipo'], $preco, $inicial, $minimo]);

                    // Se já começa com quantidade, registra como uma entrada no histórico
                    if ($inicial > 0) {
                        $novo_id = $pdo->lastInsertId();
                        $stmt = $pdo->prepare("INSERT INTO movimentacoes (produto_id, tipo, quantidade, usuario_id) VALUES (?, 'entrada', ?, ?)");
                        $stmt->execute([$novo_id, $inicial, $_SESSION['usuario_id']]);
                    }
                    $pdo->commit();
                } catch (Exception $erro) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $erro;
                }
                definir_mensagem('sucesso', 'Produto cadastrado.');
            } else {
                $stmt = $pdo->prepare('UPDATE produtos SET nome = ?, preco = ?, estoque_minimo = ? WHERE id = ?');
                $stmt->execute([$prod['nome'], $preco, $minimo, $prod['id']]);
                definir_mensagem('sucesso', 'Produto atualizado.');
            }
            header('Location: estoque.php');
            exit;
        }
    }
}

// Clicou em "Editar": carrega o produto no formulário
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $eh_gerente && isset($_GET['editar'])) {
    $stmt = $pdo->prepare('SELECT id, nome, tipo, preco, quantidade, estoque_minimo FROM produtos WHERE id = ?');
    $stmt->execute([(int) $_GET['editar']]);
    $encontrado = $stmt->fetch();

    if ($encontrado) {
        $prod = [
            'id'             => (int) $encontrado['id'],
            'nome'           => $encontrado['nome'],
            'tipo'           => $encontrado['tipo'],
            'preco'          => number_format((float) $encontrado['preco'], 2, ',', ''),
            'quantidade'     => (string) $encontrado['quantidade'],
            'estoque_minimo' => (string) $encontrado['estoque_minimo'],
        ];
    } else {
        definir_mensagem('erro', 'Produto não encontrado.');
        header('Location: estoque.php');
        exit;
    }
}

// Dados para as listas
$materiais = $pdo->query("SELECT id, nome, preco, quantidade, estoque_minimo FROM produtos WHERE tipo = 'material' ORDER BY nome")->fetchAll();
$servicos  = $pdo->query("SELECT id, nome, preco FROM produtos WHERE tipo = 'servico' ORDER BY nome")->fetchAll();

$qtd_baixos = 0;
foreach ($materiais as $m) {
    if ($m['quantidade'] <= $m['estoque_minimo']) {
        $qtd_baixos++;
    }
}

$historico = $pdo->query(
    'SELECT m.data, p.nome AS produto, m.tipo, m.quantidade, u.nome AS funcionario
       FROM movimentacoes m
       JOIN produtos p ON p.id = m.produto_id
       JOIN usuarios u ON u.id = m.usuario_id
      ORDER BY m.id DESC
      LIMIT 15'
)->fetchAll();

include __DIR__ . '/../includes/header.php';
mostrar_mensagem();
?>

<?php if ($qtd_baixos > 0): ?>
    <div class="aviso aviso-alerta">
        <strong>Estoque baixo:</strong> <?= (int) $qtd_baixos ?> material(is) com quantidade igual ou abaixo do mínimo. Veja a coluna "Situação".
    </div>
<?php endif; ?>

<section class="cartao">
    <h2>Materiais em estoque</h2>
    <?php if (!$materiais): ?>
        <p>Nenhum material cadastrado ainda.</p>
    <?php else: ?>
        <div class="tabela-rolagem">
            <table>
                <thead>
                    <tr>
                        <th>Material</th>
                        <th class="numero">Preço</th>
                        <th class="numero">Em estoque</th>
                        <th class="numero">Mínimo</th>
                        <th>Situação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materiais as $m): ?>
                        <?php [$texto_situacao, $classe_situacao] = situacao_estoque($m['quantidade'], $m['estoque_minimo']); ?>
                        <tr>
                            <td><?= e($m['nome']) ?></td>
                            <td class="numero"><?= e(formatar_dinheiro($m['preco'])) ?></td>
                            <td class="numero"><?= (int) $m['quantidade'] ?></td>
                            <td class="numero"><?= (int) $m['estoque_minimo'] ?></td>
                            <td><span class="<?= $classe_situacao ?>"><?= $texto_situacao ?></span></td>
                            <td>
                                <div class="acoes">
                                    <a class="botao botao-pequeno" href="estoque.php?produto=<?= (int) $m['id'] ?>#movimentar">Movimentar</a>
                                    <?php if ($eh_gerente): ?>
                                        <a class="botao botao-secundario botao-pequeno" href="estoque.php?editar=<?= (int) $m['id'] ?>#formulario-produto">Editar</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="cartao" id="movimentar">
    <h2>Registrar entrada ou saída</h2>

    <?php if ($erros_mov): ?>
        <div class="aviso aviso-erro" role="alert">
            <strong>Corrija antes de salvar:</strong>
            <ul>
                <?php foreach ($erros_mov as $erro): ?>
                    <li><?= e($erro) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form class="formulario" method="post" action="estoque.php#movimentar">
        <input type="hidden" name="acao" value="movimentar">

        <label for="produto_id">Material</label>
        <select id="produto_id" name="produto_id" required>
            <option value="">Escolha...</option>
            <?php foreach ($materiais as $m): ?>
                <option value="<?= (int) $m['id'] ?>"<?= (int) $m['id'] === $mov['produto_id'] ? ' selected' : '' ?>>
                    <?= e($m['nome']) ?> (em estoque: <?= (int) $m['quantidade'] ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <label for="tipo_mov">Tipo</label>
        <select id="tipo_mov" name="tipo">
            <option value="entrada"<?= $mov['tipo'] === 'entrada' ? ' selected' : '' ?>>Entrada (chegou material)</option>
            <option value="saida"<?= $mov['tipo'] === 'saida' ? ' selected' : '' ?>>Saída (material usado ou retirado)</option>
        </select>

        <label for="quantidade_mov">Quantidade</label>
        <input type="number" id="quantidade_mov" name="quantidade" value="<?= e($mov['quantidade']) ?>" min="1" step="1" inputmode="numeric" required>

        <div class="botoes">
            <button type="submit" class="botao">Registrar</button>
        </div>
    </form>
</section>

<section class="cartao">
    <h2>Últimas movimentações</h2>
    <?php if (!$historico): ?>
        <p>Nenhuma movimentação registrada ainda.</p>
    <?php else: ?>
        <div class="tabela-rolagem">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Material</th>
                        <th>Tipo</th>
                        <th class="numero">Quantidade</th>
                        <th>Funcionário</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historico as $h): ?>
                        <tr>
                            <td><?= e(formatar_data_hora($h['data'])) ?></td>
                            <td><?= e($h['produto']) ?></td>
                            <td><?= $h['tipo'] === 'entrada' ? 'Entrada' : 'Saída' ?></td>
                            <td class="numero"><?= (int) $h['quantidade'] ?></td>
                            <td><?= e($h['funcionario']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php if ($eh_gerente): ?>
    <section class="cartao" id="formulario-produto">
        <h2><?= $prod['id'] > 0 ? 'Editar produto' : 'Novo produto ou serviço' ?></h2>

        <?php if ($erros_prod): ?>
            <div class="aviso aviso-erro" role="alert">
                <strong>Corrija antes de salvar:</strong>
                <ul>
                    <?php foreach ($erros_prod as $erro): ?>
                        <li><?= e($erro) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form class="formulario" method="post" action="estoque.php#formulario-produto">
            <input type="hidden" name="acao" value="salvar_produto">
            <input type="hidden" name="id" value="<?= (int) $prod['id'] ?>">

            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" value="<?= e($prod['nome']) ?>" maxlength="100" required>

            <?php if ($prod['id'] === 0): ?>
                <label for="tipo">Tipo</label>
                <select id="tipo" name="tipo">
                    <option value="material"<?= $prod['tipo'] === 'material' ? ' selected' : '' ?>>Material (controla estoque)</option>
                    <option value="servico"<?= $prod['tipo'] === 'servico' ? ' selected' : '' ?>>Serviço (só tem preço)</option>
                </select>
            <?php else: ?>
                <p class="dica">Tipo: <?= $prod['tipo'] === 'material' ? 'material' : 'serviço' ?> (não pode ser mudado)</p>
            <?php endif; ?>

            <label for="preco">Preço (use vírgula, ex.: 12,90)</label>
            <input type="text" id="preco" name="preco" value="<?= e($prod['preco']) ?>" inputmode="decimal" placeholder="0,00" required>

            <?php if ($prod['tipo'] === 'material' || $prod['id'] === 0): ?>
                <label for="estoque_minimo">Estoque mínimo (abaixo disso aparece o aviso)</label>
                <input type="number" id="estoque_minimo" name="estoque_minimo" value="<?= e($prod['estoque_minimo']) ?>" min="0" step="1" inputmode="numeric">

                <?php if ($prod['id'] === 0): ?>
                    <label for="quantidade">Quantidade inicial (só para materiais)</label>
                    <input type="number" id="quantidade" name="quantidade" value="<?= e($prod['quantidade']) ?>" min="0" step="1" inputmode="numeric">
                <?php else: ?>
                    <p class="dica">A quantidade em estoque só muda por entrada ou saída.</p>
                <?php endif; ?>
            <?php endif; ?>

            <div class="botoes">
                <button type="submit" class="botao">Salvar</button>
                <?php if ($prod['id'] > 0): ?>
                    <a href="estoque.php" class="botao botao-secundario">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </section>
<?php endif; ?>

<section class="cartao">
    <h2>Serviços e preços</h2>
    <?php if (!$servicos): ?>
        <p>Nenhum serviço cadastrado ainda.</p>
    <?php else: ?>
        <div class="tabela-rolagem">
            <table>
                <thead>
                    <tr>
                        <th>Serviço</th>
                        <th class="numero">Preço</th>
                        <?php if ($eh_gerente): ?><th>Ações</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servicos as $s): ?>
                        <tr>
                            <td><?= e($s['nome']) ?></td>
                            <td class="numero"><?= e(formatar_dinheiro($s['preco'])) ?></td>
                            <?php if ($eh_gerente): ?>
                                <td>
                                    <a class="botao botao-secundario botao-pequeno" href="estoque.php?editar=<?= (int) $s['id'] ?>#formulario-produto">Editar</a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

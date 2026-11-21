<?php
// Somente gerente.
// Esta página lista, cadastra, edita e ativa/desativa.

require_once __DIR__ . '/../includes/auth.php';
exigir_gerente();

$titulo_pagina = 'Funcionários';
$raiz   = '../';
$erros  = [];
$meu_id = (int) $_SESSION['usuario_id'];

// Valores que aparecem no formulário (vazio = novo funcionário)
$form = ['id' => 0, 'nome' => '', 'login' => '', 'nivel' => 'funcionario'];

// Quando um formulário é enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // Salvar: cadastrar (id = 0) ou editar (id maior que 0)
    if ($acao === 'salvar') {
        $form['id']    = (int) ($_POST['id'] ?? 0);
        $form['nome']  = trim($_POST['nome'] ?? '');
        $form['login'] = trim($_POST['login'] ?? '');
        $form['nivel'] = $_POST['nivel'] ?? '';
        $senha         = $_POST['senha'] ?? '';

        if ($form['nome'] === '' || mb_strlen($form['nome']) > 100) {
            $erros[] = 'Informe o nome (até 100 letras).';
        }
        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $form['login'])) {
            $erros[] = 'O usuário deve ter de 3 a 50 caracteres: letras, números, ponto, traço ou sublinhado.';
        }
        if (!in_array($form['nivel'], ['gerente', 'funcionario'], true)) {
            $erros[] = 'Escolha um nível válido.';
        }
        if ($form['id'] === 0 && $senha === '') {
            $erros[] = 'Informe uma senha para o novo funcionário.';
        }
        if ($senha !== '' && strlen($senha) < 6) {
            $erros[] = 'A senha deve ter pelo menos 6 caracteres.';
        }
        if ($form['id'] === $meu_id && $form['nivel'] !== 'gerente') {
            $erros[] = 'Você não pode tirar o seu próprio nível de gerente.';
        }

        // Ao editar, confere se o funcionário existe
        if (!$erros && $form['id'] > 0) {
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE id = ?');
            $stmt->execute([$form['id']]);
            if (!$stmt->fetch()) {
                $erros[] = 'Funcionário não encontrado.';
            }
        }

        // O usuário (login) não pode se repetir
        if (!$erros) {
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE login = ? AND id <> ?');
            $stmt->execute([$form['login'], $form['id']]);
            if ($stmt->fetch()) {
                $erros[] = 'Já existe um funcionário com esse usuário.';
            }
        }

        if (!$erros) {
            if ($form['id'] === 0) {
                $stmt = $pdo->prepare('INSERT INTO usuarios (nome, login, senha, nivel) VALUES (?, ?, ?, ?)');
                $stmt->execute([$form['nome'], $form['login'], password_hash($senha, PASSWORD_DEFAULT), $form['nivel']]);
                definir_mensagem('sucesso', 'Funcionário cadastrado.');
            } elseif ($senha === '') {
                // Senha em branco na edição = mantém a senha atual
                $stmt = $pdo->prepare('UPDATE usuarios SET nome = ?, login = ?, nivel = ? WHERE id = ?');
                $stmt->execute([$form['nome'], $form['login'], $form['nivel'], $form['id']]);
                definir_mensagem('sucesso', 'Funcionário atualizado.');
            } else {
                $stmt = $pdo->prepare('UPDATE usuarios SET nome = ?, login = ?, nivel = ?, senha = ? WHERE id = ?');
                $stmt->execute([$form['nome'], $form['login'], $form['nivel'], password_hash($senha, PASSWORD_DEFAULT), $form['id']]);
                definir_mensagem('sucesso', 'Funcionário atualizado com a nova senha.');
            }
            header('Location: funcionarios.php');   // evita reenviar o formulário ao atualizar a página
            exit;
        }
    }

    // Ativar ou desativar
    elseif ($acao === 'status') {
        $id    = (int) ($_POST['id'] ?? 0);
        $ativo = !empty($_POST['ativo']) ? 1 : 0;

        if ($id === $meu_id) {
            definir_mensagem('erro', 'Você não pode desativar a sua própria conta.');
        } else {
            $stmt = $pdo->prepare('UPDATE usuarios SET ativo = ? WHERE id = ?');
            $stmt->execute([$ativo, $id]);
            definir_mensagem('sucesso', $ativo ? 'Funcionário ativado.' : 'Funcionário desativado.');
        }
        header('Location: funcionarios.php');
        exit;
    }
}

// Clicou em "Editar": carrega os dados no formulário
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['editar'])) {
    $stmt = $pdo->prepare('SELECT id, nome, login, nivel FROM usuarios WHERE id = ?');
    $stmt->execute([(int) $_GET['editar']]);
    $encontrado = $stmt->fetch();

    if ($encontrado) {
        $form = $encontrado;
        $form['id'] = (int) $form['id'];
    } else {
        definir_mensagem('erro', 'Funcionário não encontrado.');
        header('Location: funcionarios.php');
        exit;
    }
}

// Lista de funcionários
$funcionarios = $pdo->query(
    'SELECT id, nome, login, nivel, ativo, criado_em FROM usuarios ORDER BY ativo DESC, nome'
)->fetchAll();

include __DIR__ . '/../includes/header.php';
mostrar_mensagem();
?>

<section class="cartao" id="formulario">
    <h2><?= $form['id'] > 0 ? 'Editar funcionário' : 'Novo funcionário' ?></h2>

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

    <form class="formulario" method="post" action="funcionarios.php">
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= (int) $form['id'] ?>">

        <label for="nome">Nome</label>
        <input type="text" id="nome" name="nome" value="<?= e($form['nome']) ?>" maxlength="100" required>

        <label for="login">Usuário (para entrar no sistema)</label>
        <input type="text" id="login" name="login" value="<?= e($form['login']) ?>" maxlength="50" autocomplete="off" required>

        <label for="nivel">Nível de acesso</label>
        <select id="nivel" name="nivel">
            <option value="funcionario"<?= $form['nivel'] === 'funcionario' ? ' selected' : '' ?>>Funcionário</option>
            <option value="gerente"<?= $form['nivel'] === 'gerente' ? ' selected' : '' ?>>Gerente</option>
        </select>

        <label for="senha"><?= $form['id'] > 0 ? 'Nova senha (deixe em branco para manter a atual)' : 'Senha (mínimo 6 caracteres)' ?></label>
        <input type="password" id="senha" name="senha" autocomplete="new-password"<?= $form['id'] > 0 ? '' : ' required' ?>>

        <div class="botoes">
            <button type="submit" class="botao">Salvar</button>
            <?php if ($form['id'] > 0): ?>
                <a href="funcionarios.php" class="botao botao-secundario">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="cartao">
    <h2>Funcionários cadastrados</h2>
    <div class="tabela-rolagem">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Usuário</th>
                    <th>Nível</th>
                    <th>Situação</th>
                    <th>Cadastro</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($funcionarios as $f): ?>
                    <tr>
                        <td><?= e($f['nome']) ?><?= (int) $f['id'] === $meu_id ? ' (você)' : '' ?></td>
                        <td><?= e($f['login']) ?></td>
                        <td><?= e(nome_do_nivel($f['nivel'])) ?></td>
                        <td>
                            <span class="<?= $f['ativo'] ? 'situacao-ativo' : 'situacao-inativo' ?>">
                                <?= $f['ativo'] ? 'Ativo' : 'Desativado' ?>
                            </span>
                        </td>
                        <td><?= e(formatar_data($f['criado_em'])) ?></td>
                        <td>
                            <div class="acoes">
                                <a class="botao botao-secundario botao-pequeno" href="funcionarios.php?editar=<?= (int) $f['id'] ?>#formulario">Editar</a>

                                <?php if ((int) $f['id'] !== $meu_id): ?>
                                    <form method="post" action="funcionarios.php"
                                          onsubmit="return confirm('<?= $f['ativo'] ? 'Desativar' : 'Ativar' ?> este funcionário?');">
                                        <input type="hidden" name="acao" value="status">
                                        <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                                        <input type="hidden" name="ativo" value="<?= $f['ativo'] ? 0 : 1 ?>">
                                        <button type="submit" class="botao botao-pequeno <?= $f['ativo'] ? 'botao-perigo' : '' ?>">
                                            <?= $f['ativo'] ? 'Desativar' : 'Ativar' ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

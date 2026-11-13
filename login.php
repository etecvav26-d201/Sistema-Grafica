<?php
require_once __DIR__ . '/includes/auth.php';

// Quem já está logado vai direto para o início
if (!empty($_SESSION['usuario_id'])) {
    header('Location: admin/inicio.php');
    exit;
}

$erro  = '';
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($login === '' || $senha === '') {
        $erro = 'Preencha o usuário e a senha.';
    } else {
        $stmt = $pdo->prepare('SELECT id, nome, senha, nivel, ativo FROM usuarios WHERE login = ?');
        $stmt->execute([$login]);
        $usuario = $stmt->fetch();

        if ($usuario && $usuario['ativo'] == 1 && password_verify($senha, $usuario['senha'])) {
            session_regenerate_id(true);   // novo id de sessão após o login
            $_SESSION['usuario_id']    = (int) $usuario['id'];
            $_SESSION['nome']          = $usuario['nome'];
            $_SESSION['nivel']         = $usuario['nivel'];
            $_SESSION['ultimo_acesso'] = time();
            header('Location: admin/inicio.php');
            exit;
        }

        // Mesma mensagem para usuário inexistente, senha errada ou conta desativada
        $erro = 'Usuário ou senha inválidos.';
    }
}

// Avisos vindos de outras páginas (textos fixos, não vêm do usuário)
$aviso = '';
if (($_GET['aviso'] ?? '') === 'expirou') {
    $aviso = 'Sua sessão expirou. Entre novamente.';
} elseif (($_GET['aviso'] ?? '') === 'saiu') {
    $aviso = 'Você saiu do sistema.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar – <?= e(NOME_SISTEMA) ?></title>
    <link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body>
<div class="faixa-cmyk" aria-hidden="true"></div>
<main class="login">
    <h1><?= e(NOME_SISTEMA) ?></h1>

    <?php if ($erro !== ''): ?>
        <div class="aviso aviso-erro" role="alert"><strong>Erro:</strong> <?= e($erro) ?></div>
    <?php endif; ?>
    <?php if ($aviso !== ''): ?>
        <div class="aviso aviso-alerta"><?= e($aviso) ?></div>
    <?php endif; ?>

    <form class="cartao formulario" method="post" action="login.php">
        <label for="login">Usuário</label>
        <input type="text" id="login" name="login" value="<?= e($login) ?>" autocomplete="username" required autofocus>

        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" autocomplete="current-password" required>

        <button type="submit" class="botao">Entrar</button>
    </form>
</main>
</body>
</html>

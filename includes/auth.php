<?php
// Login e nível de acesso. Use no começo de cada página do admin/:
//   require_once __DIR__ . '/../includes/auth.php';
//   exigir_login();     // qualquer usuário logado
//   exigir_gerente();   // somente o gerente
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/funcoes.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),   // só exige HTTPS quando o site usa HTTPS
        'httponly' => true,                         // o JavaScript não consegue ler o cookie da sessão
        'samesite' => 'Lax',
    ]);
    session_start();
}

define('TEMPO_SESSAO', 30 * 60);   // 30 minutos sem usar o sistema = sai sozinho

// Barra quem não está logado. (As páginas do admin/ ficam um nível abaixo do login.php.)
function exigir_login() {
    global $pdo;

    if (empty($_SESSION['usuario_id'])) {
        header('Location: ../login.php');
        exit;
    }

    if (time() - ($_SESSION['ultimo_acesso'] ?? 0) > TEMPO_SESSAO) {
        $_SESSION = [];
        session_destroy();
        header('Location: ../login.php?aviso=expirou');
        exit;
    }
    $_SESSION['ultimo_acesso'] = time();

    // Confere no banco se a conta continua ativa e qual é o nível dela agora
    $stmt = $pdo->prepare('SELECT nome, nivel, ativo FROM usuarios WHERE id = ?');
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();

    if (!$usuario || $usuario['ativo'] != 1) {
        $_SESSION = [];
        session_destroy();
        header('Location: ../login.php');
        exit;
    }

    $_SESSION['nome']  = $usuario['nome'];
    $_SESSION['nivel'] = $usuario['nivel'];
}

// Barra quem não é gerente (mesmo que digite o endereço da página direto no navegador).
function exigir_gerente() {
    exigir_login();

    if ($_SESSION['nivel'] !== 'gerente') {
        http_response_code(403);
        $titulo_pagina = 'Acesso negado';
        $raiz = '../';
        include __DIR__ . '/header.php';
        echo '<div class="aviso aviso-erro"><strong>Acesso negado:</strong> esta página é só para o gerente.</div>';
        include __DIR__ . '/footer.php';
        exit;
    }
}

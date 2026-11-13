<?php
// Topo e menu repetidos em todas as páginas do admin/.
// Cada página define antes de incluir este arquivo:
//   $titulo_pagina = 'Nome da página';
//   $raiz = '../';   (caminho até a pasta principal do projeto)
$titulo_pagina = $titulo_pagina ?? 'Início';
$raiz          = $raiz ?? '';
$pagina_atual  = basename($_SERVER['PHP_SELF']);
$nivel         = $_SESSION['nivel'] ?? '';

// arquivo => [texto do menu, aparece só para o gerente?]
$menu = [
    'inicio.php'       => ['Início', false],
    'vendas.php'       => ['Vendas', false],
    'impressao.php'    => ['Preço de impressão', false],
    'estoque.php'      => ['Estoque', false],
    'gastos.php'       => ['Gastos', true],
    'funcionarios.php' => ['Funcionários', true],
    'relatorio.php'    => ['Relatório', true],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo_pagina) ?> – <?= e(NOME_SISTEMA) ?></title>
    <link rel="stylesheet" href="<?= $raiz ?>assets/css/estilo.css">
</head>
<body>
<div class="faixa-cmyk" aria-hidden="true"></div>
<header class="topo">
    <!-- Para usar o logo: troque o texto abaixo por <img src="<?= $raiz ?>logo/logo.png" alt="..."> -->
    <a class="marca" href="inicio.php"><?= e(NOME_SISTEMA) ?></a>
    <button type="button" class="botao-menu" id="botao-menu" aria-expanded="false" aria-controls="menu">Menu</button>
    <nav class="menu" id="menu" aria-label="Menu principal">
        <?php foreach ($menu as $arquivo => [$texto, $so_gerente]): ?>
            <?php if ($so_gerente && $nivel !== 'gerente') { continue; } ?>
            <?php if (file_exists(__DIR__ . '/../admin/' . $arquivo)): ?>
                <a href="<?= $arquivo ?>"<?= $arquivo === $pagina_atual ? ' class="ativo" aria-current="page"' : '' ?>><?= e($texto) ?></a>
            <?php else: ?>
                <span class="em-breve"><?= e($texto) ?> (em breve)</span>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if (!empty($_SESSION['usuario_id'])): ?>
            <span class="usuario">Olá, <?= e($_SESSION['nome'] ?? '') ?> (<?= $nivel === 'gerente' ? 'gerente' : 'funcionário' ?>)</span>
            <a href="<?= $raiz ?>logout.php">Sair</a>
        <?php endif; ?>
    </nav>
</header>
<main class="conteudo">
    <h1><?= e($titulo_pagina) ?></h1>

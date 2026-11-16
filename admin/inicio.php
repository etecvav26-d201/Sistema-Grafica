<?php
// Página inicial: saudação e aviso de estoque baixo.
require_once __DIR__ . '/../includes/auth.php';
exigir_login();

$titulo_pagina = 'Início';
$raiz = '../';

// Materiais com quantidade igual ou abaixo do mínimo
$baixos = $pdo->query(
    "SELECT nome, quantidade, estoque_minimo FROM produtos
      WHERE tipo = 'material' AND quantidade <= estoque_minimo
      ORDER BY quantidade, nome"
)->fetchAll();

include __DIR__ . '/../includes/header.php';
mostrar_mensagem();
?>

<p>Olá, <?= e($_SESSION['nome']) ?>! Use o menu para registrar vendas, calcular impressões e consultar o estoque.</p>

<?php if ($baixos): ?>
    <div class="aviso aviso-alerta">
        <strong>Estoque baixo:</strong> estes materiais precisam de reposição.
        <ul>
            <?php foreach ($baixos as $b): ?>
                <li><?= e($b['nome']) ?>: <?= (int) $b['quantidade'] ?> em estoque (mínimo <?= (int) $b['estoque_minimo'] ?>)</li>
            <?php endforeach; ?>
        </ul>
        <a href="estoque.php">Ir para o estoque</a>
    </div>
<?php else: ?>
    <div class="aviso aviso-sucesso">
        <strong>Estoque em dia:</strong> nenhum material abaixo do mínimo.
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

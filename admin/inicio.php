<?php
// PÁGINA DE TESTE: confere o login, a conexão com o banco e o layout.
// Será substituída pela página inicial de verdade nas próximas etapas.
require_once __DIR__ . '/../includes/auth.php';
exigir_login();

$titulo_pagina = 'Início';
$raiz = '../';

// Conta os registros de cada tabela (os nomes vêm desta lista fixa, não do usuário)
$tabelas = ['usuarios', 'produtos', 'movimentacoes', 'vendas', 'gastos'];
$totais = [];
foreach ($tabelas as $tabela) {
    $totais[$tabela] = $pdo->query("SELECT COUNT(*) FROM $tabela")->fetchColumn();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="aviso aviso-sucesso">
    <strong>Login ativo:</strong> você entrou como <?= e($_SESSION['nome']) ?>
    (<?= $_SESSION['nivel'] === 'gerente' ? 'gerente' : 'funcionário' ?>).
</div>

<section class="cartao">
    <h2>Registros em cada tabela</h2>
    <div class="tabela-rolagem">
        <table>
            <thead>
                <tr><th>Tabela</th><th class="numero">Registros</th></tr>
            </thead>
            <tbody>
                <?php foreach ($totais as $tabela => $total): ?>
                    <tr>
                        <td><?= e($tabela) ?></td>
                        <td class="numero"><?= (int) $total ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

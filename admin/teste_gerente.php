<?php
// PÁGINA TEMPORÁRIA DA ETAPA 2: serve só para testar a trava de nível de acesso.
// Será apagada na Etapa 3.
require_once __DIR__ . '/../includes/auth.php';
exigir_gerente();

$titulo_pagina = 'Teste: área do gerente';
$raiz = '../';
include __DIR__ . '/../includes/header.php';
?>

<div class="aviso aviso-sucesso"><strong>Acesso liberado:</strong> você é gerente.</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

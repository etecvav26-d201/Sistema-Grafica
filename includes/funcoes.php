<?php
// Funções pequenas que serão usadas em várias páginas.

// Escapa texto antes de mostrar na tela (evita XSS). Use sempre: echo e($variavel)
function e($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

// 1234.5 -> "R$ 1.234,50"
function formatar_dinheiro($valor) {
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

// "2026-11-09" -> "09/11/2026"
function formatar_data($data) {
    return $data ? date('d/m/Y', strtotime($data)) : '';
}

// "2026-11-09 14:30:00" -> "09/11/2026 14:30"
function formatar_data_hora($data) {
    return $data ? date('d/m/Y H:i', strtotime($data)) : '';
}

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

// 'gerente' -> "Gerente"; qualquer outro valor -> "Funcionário"
function nome_do_nivel($nivel) {
    return $nivel === 'gerente' ? 'Gerente' : 'Funcionário';
}

// Guarda uma mensagem para aparecer na próxima página (usada depois de salvar e redirecionar).
// $tipo pode ser: sucesso, erro ou alerta
function definir_mensagem($tipo, $texto) {
    $_SESSION['mensagem'] = ['tipo' => $tipo, 'texto' => $texto];
}

// Mostra a mensagem guardada (se houver) e apaga, para aparecer uma vez só.
function mostrar_mensagem() {
    if (empty($_SESSION['mensagem'])) {
        return;
    }
    $m = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']);

    $rotulos = ['sucesso' => 'Sucesso', 'erro' => 'Erro', 'alerta' => 'Atenção'];
    $tipo    = isset($rotulos[$m['tipo']]) ? $m['tipo'] : 'alerta';

    echo '<div class="aviso aviso-' . $tipo . '" role="status"><strong>'
       . $rotulos[$tipo] . ':</strong> ' . e($m['texto']) . '</div>';
}

// Converte texto digitado em número com centavos. Aceita "12,90", "12.90" e "1.234,50".
// Devolve null se não for um número válido.
function para_decimal($texto) {
    $texto = str_replace(['R$', ' '], '', trim((string) $texto));
    if (strpos($texto, ',') !== false) {
        $texto = str_replace('.', '', $texto);    // tira o ponto de milhar
        $texto = str_replace(',', '.', $texto);   // vírgula vira ponto decimal
    }
    return is_numeric($texto) ? round((float) $texto, 2) : null;
}

// Converte texto em número inteiro entre $min e $max. Devolve null se não for válido.
function para_inteiro($texto, $min, $max) {
    $valor = filter_var(trim((string) $texto), FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);
    return $valor === false ? null : $valor;
}

// Situação de um material no estoque: devolve [texto, classe do CSS]
function situacao_estoque($quantidade, $minimo) {
    if ($quantidade <= 0) {
        return ['Zerado', 'situacao-inativo'];
    }
    if ($quantidade <= $minimo) {
        return ['Baixo', 'situacao-baixo'];
    }
    return ['OK', 'situacao-ativo'];
}

// Confere se o texto é uma data real no formato AAAA-MM-DD (o formato dos campos de data).
// Devolve o próprio texto se for válida, ou '' se não for.
function data_valida($texto) {
    $texto = trim((string) $texto);
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $texto, $p)) {
        return '';
    }
    return checkdate((int) $p[2], (int) $p[3], (int) $p[1]) ? $texto : '';
}

<?php
// Etapa 6: relatório de vendas, gastos e estoque. Somente o gerente.
// O envio abre o programa de e-mail (mailto) ou o Gmail no navegador, com a mensagem pronta.
// O gerente confere e clica em enviar. O destinatário vem de includes/config.php (EMAIL_DIRETORIA),
// nunca de um campo digitado nesta página.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/relatorio_funcoes.php';
exigir_gerente();

$titulo_pagina = 'Relatório';
$raiz = '../';

// Período: por padrão, do dia 1 do mês até hoje
$de  = data_valida($_GET['de'] ?? '');
$ate = data_valida($_GET['ate'] ?? '');
if ($de === '')  { $de  = date('Y-m-01'); }
if ($ate === '') { $ate = date('Y-m-d'); }

$erro_periodo = ($de > $ate) ? 'A data inicial não pode ser depois da data final.' : '';

$destino    = EMAIL_DIRETORIA;
$destino_ok = filter_var($destino, FILTER_VALIDATE_EMAIL) && strpos($destino, '@exemplo.com.br') === false;

$dados       = null;
$link_mailto = '';
$link_gmail  = '';
$resumido    = false;

if ($erro_periodo === '') {
    $dados = montar_relatorio($pdo, $de, $ate);

    if ($destino_ok) {
        $assunto = 'Relatório da ' . NOME_SISTEMA . ' - ' . formatar_data($de) . ' a ' . formatar_data($ate);
        $corpo   = relatorio_texto($dados, true);

        // Links de e-mail têm limite de tamanho: se passar, usa a versão resumida no mailto
        $link_mailto = 'mailto:' . $destino . '?subject=' . rawurlencode($assunto) . '&body=' . rawurlencode($corpo);
        if (strlen($link_mailto) > 1800) {
            $link_mailto = 'mailto:' . $destino . '?subject=' . rawurlencode($assunto) . '&body=' . rawurlencode(relatorio_texto($dados, false));
            $resumido = true;
        }

        $link_gmail = 'https://mail.google.com/mail/?view=cm&fs=1&to=' . rawurlencode($destino)
                    . '&su=' . rawurlencode($assunto) . '&body=' . rawurlencode($corpo);
    }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="cartao">
    <h2>Período do relatório</h2>
    <form class="formulario" method="get" action="relatorio.php">
        <div class="linha-campos">
            <div>
                <label for="de">De</label>
                <input type="date" id="de" name="de" value="<?= e($de) ?>" required>
            </div>
            <div>
                <label for="ate">Até</label>
                <input type="date" id="ate" name="ate" value="<?= e($ate) ?>" required>
            </div>
        </div>
        <div class="botoes">
            <button type="submit" class="botao">Gerar prévia</button>
        </div>
    </form>
</section>

<?php if ($erro_periodo !== ''): ?>
    <div class="aviso aviso-erro" role="alert"><strong>Erro:</strong> <?= e($erro_periodo) ?></div>
<?php else: ?>
    <section class="cartao">
        <h2>Prévia do relatório</h2>
        <div class="tabela-rolagem">
            <?= relatorio_html($dados) ?>
        </div>
    </section>

    <section class="cartao">
        <h2>Enviar para a diretoria da APM</h2>

        <?php if (!$destino_ok): ?>
            <div class="aviso aviso-alerta">
                <strong>Atenção:</strong> o e-mail da diretoria ainda não foi configurado.
                Ajuste <code>EMAIL_DIRETORIA</code> em <code>includes/config.php</code>.
            </div>
        <?php else: ?>
            <p>A mensagem será preparada para <strong><?= e($destino) ?></strong>. Confira o texto e clique em enviar no seu e-mail.</p>

            <div class="botoes">
                <a class="botao" href="<?= e($link_mailto) ?>">Abrir no programa de e-mail</a>
                <a class="botao botao-secundario" href="<?= e($link_gmail) ?>" target="_blank" rel="noopener">Abrir no Gmail (navegador)</a>
            </div>

            <?php if ($resumido): ?>
                <p class="dica">O texto do primeiro botão foi resumido, porque os programas de e-mail limitam o tamanho do link.</p>
            <?php endif; ?>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

// Abre e fecha o menu no celular.
var botaoMenu = document.getElementById('botao-menu');
var menu = document.getElementById('menu');

if (botaoMenu && menu) {
    botaoMenu.addEventListener('click', function () {
        var aberto = menu.classList.toggle('aberto');
        botaoMenu.setAttribute('aria-expanded', aberto ? 'true' : 'false');
    });
}

// Mostra o total na hora (calculadora de impressão e nova venda).
// O valor que vale é o calculado no servidor; aqui é só uma prévia.
document.querySelectorAll('[data-calculo]').forEach(function (form) {
    var selecao    = form.querySelector('[data-calc-item]');
    var quantidade = form.querySelector('[data-calc-qtd]');
    var saida      = form.querySelector('[data-calc-total]');
    var link       = form.querySelector('[data-calc-link]');

    if (!selecao || !quantidade || !saida) {
        return;
    }

    function atualizar() {
        var opcao  = selecao.options[selecao.selectedIndex];
        var preco  = opcao ? parseFloat(opcao.getAttribute('data-preco')) : NaN;
        var qtd    = parseInt(quantidade.value, 10);
        var valido = !isNaN(preco) && !isNaN(qtd) && qtd >= 1;

        // Conta em centavos para evitar erro de arredondamento
        var total = valido ? (Math.round(preco * 100) * qtd) / 100 : 0;
        saida.textContent = total.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

        if (link) {
            link.hidden = !valido;
            if (valido) {
                link.href = link.getAttribute('data-base') + '?produto=' + selecao.value + '&quantidade=' + qtd + '#nova-venda';
            }
        }
    }

    selecao.addEventListener('change', atualizar);
    quantidade.addEventListener('input', atualizar);
    atualizar();
});

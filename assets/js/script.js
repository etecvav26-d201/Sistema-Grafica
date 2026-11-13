// Abre e fecha o menu no celular.
var botaoMenu = document.getElementById('botao-menu');
var menu = document.getElementById('menu');

if (botaoMenu && menu) {
    botaoMenu.addEventListener('click', function () {
        var aberto = menu.classList.toggle('aberto');
        botaoMenu.setAttribute('aria-expanded', aberto ? 'true' : 'false');
    });
}

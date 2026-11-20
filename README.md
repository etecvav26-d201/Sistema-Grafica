# Sistema Gestor – Gráfica Escolar

Sistema web (PHP, MySQL, HTML, CSS e JavaScript) para os funcionários da gráfica escolar da
Etec Vasco Antonio Venchiarutti, Jundiaí.

## Como instalar (XAMPP)

1. Copie a pasta `grafica-escolar` para `C:\xampp\htdocs\`.
2. Abra o XAMPP e ligue o **Apache** e o **MySQL**.
3. Acesse `http://localhost/phpmyadmin`, vá em **Importar** e envie o arquivo `database/grafica.sql`.
4. Se o seu MySQL tiver senha, ajuste os dados em `includes/config.php`.
5. Abra `http://localhost/grafica-escolar/` no navegador.

## Andamento

- [x] Etapa 1 – Estrutura, banco de dados e layout base
- [x] Etapa 2 – Login e níveis de acesso
- [x] Etapa 3 – Funcionários
- [x] Etapa 4 – Estoque
- [x] Etapa 5 – Calculadora de impressão e vendas
- [x] Etapa 6 – Gastos e relatório por e-mail
- [ ] Etapa 7 – Testes, ajustes e documentação final

## Requisitos

PHP 7.3 ou mais novo (o XAMPP atual já atende) e MySQL/MariaDB.

## Usuários de teste (apenas para desenvolvimento)

| Login       | Senha    | Nível       |
|-------------|----------|-------------|
| gerente     | admin123 | gerente     |
| funcionario | admin123 | funcionário |

Troque as senhas antes de usar de verdade.

## E-mail do relatório

O relatório não é enviado sozinho: o sistema prepara a mensagem pronta e o gerente confere e envia.

1. Abra `includes/config.php` e troque `EMAIL_DIRETORIA` pelo e-mail da diretoria da APM.
   Para testar, use primeiro o seu próprio e-mail.
2. Entre como gerente, abra **Relatório**, escolha o período e clique em um dos botões:
   - **Abrir no programa de e-mail**: abre o programa de e-mail padrão do computador (Outlook, Thunderbird etc.).
   - **Abrir no Gmail (navegador)**: abre a mensagem pronta no Gmail, sem precisar de programa instalado.
3. Confira a mensagem e clique em enviar.

<?php
// Configurações gerais do sistema.
// ATENÇÃO: este arquivo terá senhas. Não envie para um repositório público.

// Banco de dados (valores padrão do XAMPP)
define('DB_HOST',    'localhost');
define('DB_NOME',    'grafica_escolar');
define('DB_USUARIO', 'root');
define('DB_SENHA',   '');

define('NOME_SISTEMA', 'Gráfica Escolar');

// E-mail da diretoria da APM (usado na Etapa 6)
define('EMAIL_DIRETORIA', 'diretoria@exemplo.com.br');

date_default_timezone_set('America/Sao_Paulo');

// Mostra erros na tela enquanto desenvolvemos. Na versão final, troque '1' por '0'.
ini_set('display_errors', '1');
error_reporting(E_ALL);

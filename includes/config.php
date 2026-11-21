<?php
// Configurações gerais do sistema.

// Banco de dados
define('DB_HOST',    'localhost');
define('DB_NOME',    'grafica_escolar');
define('DB_USUARIO', 'root');
define('DB_SENHA',   '');

define('NOME_SISTEMA', 'Gráfica Escolar');

// E-mail da diretoria da APM
define('EMAIL_DIRETORIA', 'diretoria@exemplo.com.br');

date_default_timezone_set('America/Sao_Paulo');

// Mostra erros na tela
ini_set('display_errors', '1');
error_reporting(E_ALL);

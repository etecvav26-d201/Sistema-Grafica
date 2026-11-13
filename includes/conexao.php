<?php
// Cria a conexão com o MySQL usando PDO. Quem incluir este arquivo ganha a variável $pdo.
require_once __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NOME . ';charset=utf8mb4',
        DB_USUARIO,
        DB_SENHA,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // mostra erros de SQL
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // resultados como array com nomes
            PDO::ATTR_EMULATE_PREPARES   => false,                   // prepared statements de verdade
        ]
    );
} catch (PDOException $erro) {
    die('Não foi possível conectar ao banco de dados. '
      . 'Confira se o MySQL está ligado, se o arquivo database/grafica.sql foi importado '
      . 'e se os dados em includes/config.php estão certos.');
}

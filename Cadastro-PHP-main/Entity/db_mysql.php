<?php
// db_mysql.php — Conexão PDO para MySQL
// Configure as credenciais ao chamar getConexaoMySQL() ou via variáveis.

function getConexaoMySQL(string $host, string $db, string $user, string $pass, int $port = 3306): PDO
{
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $db);
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    return new PDO($dsn, $user, $pass, $opts);
}

// Exemplo de uso (comente quando usar):
// $pdo = getConexaoMySQL('127.0.0.1', 'meu_bd', 'root', 'senha');

?>

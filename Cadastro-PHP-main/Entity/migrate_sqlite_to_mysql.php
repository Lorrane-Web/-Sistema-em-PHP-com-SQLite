<?php
/**
 * migrate_sqlite_to_mysql.php
 * Script PHP para migrar a tabela `usuarios` do SQLite (Entity/database.db)
 * para um banco MySQL.
 *
 * Uso: rodar em linha de comando na raiz do projeto:
 * php Entity/migrate_sqlite_to_mysql.php
 *
 * Configure as variáveis abaixo com suas credenciais MySQL.
 */

require_once __DIR__ . '/db.php'; // getConexao() para SQLite
require_once __DIR__ . '/db_mysql.php'; // getConexaoMySQL()

$mysqlHost = '127.0.0.1';
$mysqlPort = 3306;
$mysqlDb   = 'meu_bd';
$mysqlUser = 'root';
$mysqlPass = '';

echo "Conectando ao SQLite...\n";
$sqlite = getConexao();

echo "Conectando ao MySQL {$mysqlHost}:{$mysqlPort} (database={$mysqlDb})...\n";
$mysql = getConexaoMySQL($mysqlHost, $mysqlDb, $mysqlUser, $mysqlPass, $mysqlPort);

// Cria tabela no MySQL se não existir
$create = <<<SQL
CREATE TABLE IF NOT EXISTS usuarios (
  id INT NOT NULL PRIMARY KEY,
  nome VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

echo "Garantindo existência da tabela `usuarios` no MySQL...\n";
$mysql->exec($create);

// Carrega todos os registros do SQLite
echo "Lendo registros do SQLite...\n";
$rows = $sqlite->query('SELECT id,nome,email,senha,criado_em FROM usuarios')->fetchAll(PDO::FETCH_ASSOC);
echo "Encontrados " . count($rows) . " registros.\n";

// Inserção com transação
$insert = $mysql->prepare('INSERT INTO usuarios (id,nome,email,senha,criado_em) VALUES (:id,:nome,:email,:senha,:criado_em)');
$mysql->beginTransaction();
$count = 0;
foreach ($rows as $r) {
    try {
        $insert->execute([
            ':id' => $r['id'],
            ':nome' => $r['nome'],
            ':email' => $r['email'],
            ':senha' => $r['senha'],
            ':criado_em' => $r['criado_em'],
        ]);
        $count++;
    } catch (Exception $e) {
        // Possível duplicata ou erro; mostra mensagem e continua
        echo "WARN: não foi possível inserir id={$r['id']} ({$r['email']}): " . $e->getMessage() . "\n";
    }
}
$mysql->commit();

echo "Migração finalizada. Inseridos: {$count} registros.\n";

echo "Dica: verifique com: mysql -u {$mysqlUser} -p -e \"SELECT COUNT(*) FROM {$mysqlDb}.usuarios;\"\n";

?>

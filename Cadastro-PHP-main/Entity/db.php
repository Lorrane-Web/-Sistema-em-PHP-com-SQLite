<?php
// ============================================================
// Entity/db.php — Conexão com o banco de dados SQLite via PDO
// ============================================================

define('DB_PATH', __DIR__ . '/database.db');

function getConexao(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // 1. Tabela de Usuários (Sua tabela original)
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS usuarios (
                    id       INTEGER PRIMARY KEY AUTOINCREMENT,
                    nome     TEXT    NOT NULL,
                    email    TEXT    NOT NULL UNIQUE,
                    senha    TEXT    NOT NULL,
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // 2. NOVA TABELA: Shows Cadastrados
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS shows (
                    id       INTEGER PRIMARY KEY AUTOINCREMENT,
                    artista  TEXT NOT NULL,
                    data_show TEXT NOT NULL,
                    local    TEXT NOT NULL,
                    preco    REAL NOT NULL
                )
            ");

            // 3. NOVA TABELA: Ingressos Emitidos
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS ingressos (
                    id         INTEGER PRIMARY KEY AUTOINCREMENT,
                    show_id    INTEGER NOT NULL,
                    usuario_id INTEGER NOT NULL,
                    comprado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (show_id) REFERENCES shows(id),
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
                )
            ");
        } catch (PDOException $e) {
            die('Erro ao conectar ao banco de dados: ' . $e->getMessage());
        }
    }

    return $pdo;
}
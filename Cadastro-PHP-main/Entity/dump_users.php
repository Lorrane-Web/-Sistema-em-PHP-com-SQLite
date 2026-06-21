<?php
require __DIR__ . '/db.php';
$pdo = getConexao();
$rows = $pdo->query("SELECT id,nome,email,criado_em FROM usuarios")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);

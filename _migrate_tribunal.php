<?php
// Script de migração único — delete após executar
require_once __DIR__ . '/api/config/Env.php';
Env::load(__DIR__ . '/.env');
require_once __DIR__ . '/api/config/Database.php';

$pdo = (new Database())->connect();

try {
    $pdo->exec("ALTER TABLE processos ADD COLUMN tribunal VARCHAR(20) NOT NULL DEFAULT 'TJMG' AFTER numero_processo");
    echo "✓ Coluna 'tribunal' adicionada com sucesso.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "ℹ Coluna 'tribunal' já existe.\n";
    } else {
        http_response_code(500);
        echo "✗ Erro: " . $e->getMessage() . "\n";
    }
}

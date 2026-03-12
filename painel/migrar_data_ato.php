<?php
// Script de migração único — delete após executar
if (function_exists('opcache_reset')) opcache_reset();

require_once dirname(__DIR__) . '/api/config/Env.php';
Env::load(dirname(__DIR__) . '/.env');
require_once dirname(__DIR__) . '/api/config/Database.php';

$pdo = (new Database())->connect();
try {
    $pdo->exec("ALTER TABLE processos ADD COLUMN data_ato DATE NULL AFTER tribunal");
    echo json_encode(["status" => "coluna data_ato adicionada"]);
} catch (PDOException $e) {
    echo json_encode(["status" => strpos($e->getMessage(), 'Duplicate') !== false ? "coluna ja existe" : $e->getMessage()]);
}

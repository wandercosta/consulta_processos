<?php
/**
 * Migração: cria a tabela robot_config usada pelo controle do daemon Python.
 * Acesse via browser: http://localhost/processos_api/painel/migrar_robot.php
 * (ou a URL do seu servidor)
 * Pode ser excluído após executar com sucesso.
 */

define('PAINEL_ROOT', __DIR__);
require_once dirname(__DIR__) . '/api/config/Env.php';
Env::load(dirname(__DIR__) . '/.env');
require_once dirname(__DIR__) . '/api/config/Database.php';

try {
    $db = (new Database())->connect();

    $db->exec("
        CREATE TABLE IF NOT EXISTS robot_config (
            id           INT          NOT NULL DEFAULT 1,
            ativo        TINYINT(1)   NOT NULL DEFAULT 0,
            status       VARCHAR(50)  NOT NULL DEFAULT 'parado',
            pid          INT          NULL,
            ultimo_ciclo DATETIME     NULL,
            mensagem     VARCHAR(255) NULL,
            atualizado_em DATETIME    NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Garante que a linha única existe
    $db->exec("
        INSERT IGNORE INTO robot_config (id, ativo, status)
        VALUES (1, 0, 'parado');
    ");

    echo '<p style="color:green;font-family:monospace">✓ Tabela <strong>robot_config</strong> criada (ou já existia) e linha padrão inserida.</p>';
    echo '<p>Você já pode apagar este arquivo.</p>';

} catch (Exception $e) {
    echo '<p style="color:red;font-family:monospace">✗ Erro: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

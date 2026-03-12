<?php
// Migração 005 — Cria a tabela de configuração do daemon Python

return [
    'descricao' => 'Cria tabela robot_config e insere linha padrão',
    'up' => function (PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS robot_config (
                id            INT          NOT NULL DEFAULT 1,
                ativo         TINYINT(1)   NOT NULL DEFAULT 0,
                status        VARCHAR(50)  NOT NULL DEFAULT 'parado',
                pid           INT          NULL,
                ultimo_ciclo  DATETIME     NULL,
                mensagem      VARCHAR(255) NULL,
                atualizado_em DATETIME     NULL,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Garante que a linha única de configuração existe
        $pdo->exec("
            INSERT IGNORE INTO robot_config (id, ativo, status)
            VALUES (1, 0, 'parado')
        ");
    },
];

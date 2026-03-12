<?php
// Migração 002 — Cria a tabela de logs por processo

return [
    'descricao' => 'Cria tabela processos_logs',
    'up' => function (PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS processos_logs (
                id           INT          NOT NULL AUTO_INCREMENT,
                id_processo  INT          NOT NULL,
                mensagem     TEXT         NOT NULL,
                status       VARCHAR(20)  NOT NULL DEFAULT 'INFO',
                criado_em    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_id_processo (id_processo),
                CONSTRAINT fk_logs_processo
                    FOREIGN KEY (id_processo) REFERENCES processos (id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    },
];

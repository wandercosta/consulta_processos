<?php
// Migração 001 — Cria a tabela principal de processos

return [
    'descricao' => 'Cria tabela processos',
    'up' => function (PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS processos (
                id                   INT          NOT NULL AUTO_INCREMENT,
                numero_processo      VARCHAR(50)  NOT NULL,
                status_consulta      VARCHAR(30)  NOT NULL DEFAULT 'PENDENTE',
                possui_ata           CHAR(1)      NULL,
                qtd_atas             INT          NULL DEFAULT 0,
                caminho_arquivo      VARCHAR(500) NULL,
                mensagem_erro        TEXT         NULL,
                data_ultima_consulta DATETIME     NULL,
                criado_em            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_numero_processo (numero_processo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    },
];

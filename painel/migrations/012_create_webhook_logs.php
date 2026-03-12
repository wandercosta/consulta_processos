<?php
// Migração 012 — Cria tabela webhook_logs para histórico de envios

return [
    'descricao' => 'Cria tabela webhook_logs para histórico e reenvio de webhooks',
    'up' => function (PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS webhook_logs (
                id           INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
                id_processo  INT          NOT NULL,
                url          VARCHAR(500) NOT NULL,
                payload      JSON         NOT NULL,
                status_http  SMALLINT     NULL DEFAULT NULL,
                resposta     TEXT         NULL DEFAULT NULL,
                sucesso      TINYINT(1)   NOT NULL DEFAULT 0,
                enviado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_wl_processo FOREIGN KEY (id_processo) REFERENCES processos(id) ON DELETE CASCADE
            )
        ");
    },
];

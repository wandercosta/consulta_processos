<?php
// Migração 011 — Cria tabela webhook_config (configuração global do webhook)

return [
    'descricao' => 'Cria tabela webhook_config para URL e configurações do webhook',
    'up' => function (PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS webhook_config (
                id         INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
                url        VARCHAR(500) NOT NULL,
                ativo      TINYINT(1)   NOT NULL DEFAULT 1,
                secret     VARCHAR(100) NULL DEFAULT NULL COMMENT 'Enviado no header X-Webhook-Secret',
                criado_em  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                atualizado_em DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");

        // Linha inicial em branco (URL vazia = webhook desativado)
        $count = (int)$pdo->query("SELECT COUNT(*) FROM webhook_config")->fetchColumn();
        if ($count === 0) {
            $pdo->exec("INSERT INTO webhook_config (url, ativo) VALUES ('', 0)");
        }
    },
];

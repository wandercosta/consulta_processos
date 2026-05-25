<?php
// Migração 014 — Cria a tabela de registro de arquivos excluídos (expurgo)

return [
    'descricao' => 'Cria tabela arquivos_expurgo',
    'up' => function (PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS arquivos_expurgo (
                id               INT           NOT NULL AUTO_INCREMENT,
                id_processo      INT           NOT NULL,
                numero_processo  VARCHAR(50)   NOT NULL DEFAULT '',
                nome_arquivo     VARCHAR(255)  NULL,
                caminho_arquivo  VARCHAR(500)  NOT NULL,
                formato          VARCHAR(10)   NULL,
                tamanho_bytes    BIGINT        NULL DEFAULT 0,
                baixado_em       DATETIME      NULL,
                deletado_em      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                motivo           VARCHAR(100)  NOT NULL DEFAULT 'expurgo_automatico_10d',
                PRIMARY KEY (id),
                KEY idx_expurgo_processo (id_processo),
                KEY idx_expurgo_deletado (deletado_em)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    },
];

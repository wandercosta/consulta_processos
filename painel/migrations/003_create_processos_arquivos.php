<?php
// Migração 003 — Cria a tabela de arquivos (ATAs) por processo

return [
    'descricao' => 'Cria tabela processos_arquivos',
    'up' => function (PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS processos_arquivos (
                id               INT           NOT NULL AUTO_INCREMENT,
                id_processo      INT           NOT NULL,
                nome_arquivo     VARCHAR(255)  NULL,
                caminho_arquivo  VARCHAR(500)  NULL,
                formato          VARCHAR(10)   NULL,
                tamanho_bytes    BIGINT        NULL DEFAULT 0,
                texto_doc        TEXT          NULL,
                indice           INT           NOT NULL DEFAULT 1,
                download_ok      TINYINT(1)    NOT NULL DEFAULT 0,
                criado_em        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_id_processo (id_processo),
                CONSTRAINT fk_arquivos_processo
                    FOREIGN KEY (id_processo) REFERENCES processos (id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    },
];

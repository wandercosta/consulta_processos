<?php
// Migração 013 — Cria tabela configuracoes (chave-valor para ajustes do sistema)

return [
    'descricao' => 'Cria tabela configuracoes com valores padrão do sistema',
    'up' => function (PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS configuracoes (
                chave        VARCHAR(100) NOT NULL PRIMARY KEY,
                valor        TEXT         NOT NULL,
                descricao    VARCHAR(255) NULL,
                atualizado_em DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");

        // Insere valores padrão (ignora se já existirem)
        $pdo->exec("
            INSERT IGNORE INTO configuracoes (chave, valor, descricao) VALUES
            ('max_tentativas',   '10',      'Número máximo de tentativas de busca por ATA antes de marcar como ESGOTADO'),
            ('extensoes_aceitas','pdf,html', 'Extensões de arquivo aceitas pelo robô (separadas por vírgula)')
        ");
    },
];

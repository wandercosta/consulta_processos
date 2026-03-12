<?php
// Migração 004 — Adiciona coluna tribunal à tabela processos

return [
    'descricao' => 'Adiciona coluna tribunal em processos',
    'up' => function (PDO $pdo): void {
        // Verifica se a coluna já existe antes de adicionar
        $cols = $pdo->query("SHOW COLUMNS FROM processos LIKE 'tribunal'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("
                ALTER TABLE processos
                ADD COLUMN tribunal VARCHAR(10) NOT NULL DEFAULT 'MG'
                AFTER numero_processo
            ");
        }
    },
];

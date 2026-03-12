<?php
// Migração 010 — Adiciona coluna cod_api para identificação de integração por processo

return [
    'descricao' => 'Adiciona coluna cod_api em processos (identificador de integração externo)',
    'up' => function (PDO $pdo): void {
        $cols = $pdo->query("SHOW COLUMNS FROM processos LIKE 'cod_api'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("
                ALTER TABLE processos
                ADD COLUMN cod_api VARCHAR(100) NULL DEFAULT NULL
                AFTER numero_processo
            ");
        }
    },
];

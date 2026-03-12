<?php
// Migração 006 — Adiciona coluna data_ato à tabela processos
// O daemon só consulta processos após essa data; o scraper só retorna
// documentos com data igual ou posterior.

return [
    'descricao' => 'Adiciona coluna data_ato em processos',
    'up' => function (PDO $pdo): void {
        $cols = $pdo->query("SHOW COLUMNS FROM processos LIKE 'data_ato'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("
                ALTER TABLE processos
                ADD COLUMN data_ato DATE NULL
                AFTER tribunal
            ");
        }
    },
];

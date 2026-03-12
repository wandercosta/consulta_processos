<?php
// Migração 009 — Adiciona coluna qtd_consultas para rastrear tentativas de busca sem ATA

return [
    'descricao' => 'Adiciona coluna qtd_consultas em processos (contador de tentativas sem ATA)',
    'up' => function (PDO $pdo): void {
        $cols = $pdo->query("SHOW COLUMNS FROM processos LIKE 'qtd_consultas'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("
                ALTER TABLE processos
                ADD COLUMN qtd_consultas INT NOT NULL DEFAULT 0
                AFTER qtd_atas
            ");
        }
    },
];

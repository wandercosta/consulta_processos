<?php
// Migração 008 — Converte valores de tribunal de sigla TJ para UF (ex: TJMG → MG)

return [
    'descricao' => 'Converte tribunal de sigla TJ para UF (TJMG → MG)',
    'up' => function (PDO $pdo): void {
        $pdo->exec("UPDATE processos SET tribunal = 'MG' WHERE tribunal = 'TJMG'");
        // Adicionar outros estados quando implementados:
        // $pdo->exec("UPDATE processos SET tribunal = 'SP' WHERE tribunal = 'TJSP'");
    },
];

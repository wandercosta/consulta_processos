<?php
// Migração 007 — Adiciona coluna tipo_sistema e classifica processos existentes
// Regra MG: 1º dígito 5=PJE, 0/1=EPROC, 2=PROCON. Outros estados: DESCONHECIDO.

return [
    'descricao' => 'Adiciona coluna tipo_sistema em processos e classifica existentes',
    'up' => function (PDO $pdo): void {
        $cols = $pdo->query("SHOW COLUMNS FROM processos LIKE 'tipo_sistema'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("
                ALTER TABLE processos
                ADD COLUMN tipo_sistema VARCHAR(20) NOT NULL DEFAULT 'DESCONHECIDO'
                AFTER tribunal
            ");
        }

        // Classifica processos já existentes
        $processos = $pdo->query("SELECT id, numero_processo, tribunal FROM processos")->fetchAll(PDO::FETCH_ASSOC);
        $upd = $pdo->prepare("UPDATE processos SET tipo_sistema = ? WHERE id = ?");
        foreach ($processos as $p) {
            $digitos  = preg_replace('/\D/', '', $p['numero_processo']);
            $primeiro = $digitos[0] ?? '';
            $tipo     = 'DESCONHECIDO';
            if ($p['tribunal'] === 'TJMG') {
                if ($primeiro === '5')                       $tipo = 'PJE';
                elseif (in_array($primeiro, ['0', '1'], true)) $tipo = 'EPROC';
                elseif ($primeiro === '2')                   $tipo = 'PROCON';
            }
            $upd->execute([$tipo, $p['id']]);
        }
    },
];

<?php
// Script de migração único — delete após executar
if (function_exists('opcache_reset')) opcache_reset();

require_once dirname(__DIR__) . '/api/config/Env.php';
Env::load(dirname(__DIR__) . '/.env');
require_once dirname(__DIR__) . '/api/config/Database.php';

$pdo = (new Database())->connect();
$erros = [];

// 1. Adiciona coluna tipo_sistema
try {
    $pdo->exec("ALTER TABLE processos ADD COLUMN tipo_sistema VARCHAR(20) NOT NULL DEFAULT 'DESCONHECIDO' AFTER tribunal");
    echo "✓ Coluna tipo_sistema adicionada<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false) {
        echo "ℹ Coluna tipo_sistema já existe<br>";
    } else {
        $erros[] = $e->getMessage();
    }
}

// 2. Classifica processos existentes (TJMG: 1º dígito 5=PJE, 0/1=EPROC, 2=PROCON)
$stmt = $pdo->query("SELECT id, numero_processo, tribunal FROM processos");
$processos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$atualizados = 0;
foreach ($processos as $p) {
    $digitos  = preg_replace('/\D/', '', $p['numero_processo']);
    $primeiro = $digitos[0] ?? '';
    $tipo     = 'DESCONHECIDO';
    if ($p['tribunal'] === 'TJMG') {
        if ($primeiro === '5')               $tipo = 'PJE';
        elseif (in_array($primeiro, ['0','1'])) $tipo = 'EPROC';
        elseif ($primeiro === '2')            $tipo = 'PROCON';
    }
    $pdo->prepare("UPDATE processos SET tipo_sistema = ? WHERE id = ?")->execute([$tipo, $p['id']]);
    $atualizados++;
}
echo "✓ {$atualizados} processo(s) classificado(s)<br>";

if ($erros) {
    echo "<b>Erros:</b> " . implode('<br>', $erros);
} else {
    echo "<b>Migração concluída com sucesso.</b>";
}

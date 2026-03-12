<?php
/**
 * Runner de migrações — interface web simples.
 * Acesse: http://localhost/processos_api/painel/migrations/
 *
 * Mantém uma tabela `schema_migrations` para rastrear quais migrações
 * já foram aplicadas. Execute sempre em ordem crescente.
 */

if (function_exists('opcache_reset')) opcache_reset();

require_once dirname(__DIR__, 2) . '/api/config/Env.php';
Env::load(dirname(__DIR__, 2) . '/.env');
require_once dirname(__DIR__, 2) . '/api/config/Database.php';

$pdo = (new Database())->connect();

// Garante que a tabela de controle existe
$pdo->exec("
    CREATE TABLE IF NOT EXISTS schema_migrations (
        migration  VARCHAR(100) NOT NULL,
        aplicada_em DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (migration)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Carrega migrações aplicadas
$aplicadas = $pdo->query("SELECT migration FROM schema_migrations")
                 ->fetchAll(PDO::FETCH_COLUMN);
$aplicadas = array_flip($aplicadas);

// Descobre arquivos de migração em ordem
$arquivos = glob(__DIR__ . '/[0-9][0-9][0-9]_*.php');
sort($arquivos);

$executar = $_POST['executar'] ?? null;
$mensagens = [];

// Executa uma migração específica via POST
if ($executar && isset($arquivos[array_search($executar, array_map('basename', $arquivos))])) {
    $arquivo = $executar;
    $nome    = basename($arquivo, '.php');
    if (!isset($aplicadas[$nome])) {
        try {
            $pdo->beginTransaction();
            $migration = require $arquivo;
            ($migration['up'])($pdo);
            $pdo->prepare("INSERT INTO schema_migrations (migration) VALUES (?)")->execute([$nome]);
            $pdo->commit();
            $mensagens[] = ['tipo' => 'success', 'texto' => "✓ {$nome}: {$migration['descricao']}"];
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensagens[] = ['tipo' => 'danger', 'texto' => "✗ {$nome}: " . $e->getMessage()];
        }
        // Recarrega aplicadas
        $aplicadas = array_flip(
            $pdo->query("SELECT migration FROM schema_migrations")->fetchAll(PDO::FETCH_COLUMN)
        );
    }
}

// Executa todas pendentes
if (isset($_POST['executar_todas'])) {
    foreach ($arquivos as $arquivo) {
        $nome = basename($arquivo, '.php');
        if (isset($aplicadas[$nome])) continue;
        try {
            $pdo->beginTransaction();
            $migration = require $arquivo;
            ($migration['up'])($pdo);
            $pdo->prepare("INSERT INTO schema_migrations (migration) VALUES (?)")->execute([$nome]);
            $pdo->commit();
            $mensagens[] = ['tipo' => 'success', 'texto' => "✓ {$nome}: {$migration['descricao']}"];
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensagens[] = ['tipo' => 'danger', 'texto' => "✗ {$nome}: " . $e->getMessage()];
            break; // Para na primeira falha
        }
    }
    // Recarrega aplicadas
    $aplicadas = array_flip(
        $pdo->query("SELECT migration FROM schema_migrations")->fetchAll(PDO::FETCH_COLUMN)
    );
}

$pendentes = count(array_filter($arquivos, fn($f) => !isset($aplicadas[basename($f, '.php')])));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Migrações — processos_api</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width:760px">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-database-gear me-2 text-primary"></i>Migrações do banco</h4>
            <small class="text-muted">banco: <strong><?= htmlspecialchars(Env::get('DB_NAME', 'consulta_processos')) ?></strong></small>
        </div>
        <?php if ($pendentes > 0): ?>
        <form method="post">
            <button name="executar_todas" class="btn btn-primary">
                <i class="bi bi-play-fill me-1"></i>Executar <?= $pendentes ?> pendente(s)
            </button>
        </form>
        <?php else: ?>
        <span class="badge bg-success fs-6 px-3 py-2"><i class="bi bi-check-circle me-1"></i>Tudo atualizado</span>
        <?php endif; ?>
    </div>

    <?php foreach ($mensagens as $m): ?>
    <div class="alert alert-<?= $m['tipo'] ?> py-2"><?= htmlspecialchars($m['texto']) ?></div>
    <?php endforeach; ?>

    <div class="card border-0 shadow-sm" style="border-radius:12px">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Migração</th>
                        <th>Descrição</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($arquivos as $arquivo):
                    $nome      = basename($arquivo, '.php');
                    $numero    = substr($nome, 0, 3);
                    $migration = require $arquivo;
                    $aplicada  = isset($aplicadas[$nome]);
                ?>
                <tr>
                    <td class="ps-3 text-muted small"><?= $numero ?></td>
                    <td class="font-monospace small"><?= htmlspecialchars($nome) ?></td>
                    <td class="small"><?= htmlspecialchars($migration['descricao']) ?></td>
                    <td>
                        <?php if ($aplicada): ?>
                        <span class="badge bg-success"><i class="bi bi-check me-1"></i>Aplicada</span>
                        <?php else: ?>
                        <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Pendente</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end pe-3">
                        <?php if (!$aplicada): ?>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="executar" value="<?= htmlspecialchars($arquivo) ?>">
                            <button class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-play"></i> Executar
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-muted small mt-3 mb-0">
        <i class="bi bi-info-circle me-1"></i>
        As migrações são rastreadas na tabela <code>schema_migrations</code>.
        Cada migração é executada dentro de uma transação — se falhar, o banco reverte.
    </p>
</div>
</body>
</html>

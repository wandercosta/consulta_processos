<?php
require_once 'config.php';
requerLogin();

$paginaAtual  = 'index.php';
$tituloPagina = 'Dashboard';

$db = db();

// Totais por status
$totais = [];
$stmt = $db->query("SELECT status_consulta, COUNT(*) as qtd FROM processos GROUP BY status_consulta");
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row){
    $totais[$row['status_consulta']] = (int)$row['qtd'];
}
$totalGeral = array_sum($totais);

// Finalizados com ata
$stmtAta = $db->query("SELECT COUNT(*) FROM processos WHERE status_consulta='FINALIZADO' AND possui_ata='S'");
$comAta = (int)$stmtAta->fetchColumn();

// Finalizados sem ata
$semAta = ($totais['FINALIZADO'] ?? 0) - $comAta;

// Últimos 10 processos
$ultimos = $db->query("SELECT id, numero_processo, status_consulta, criado_em FROM processos ORDER BY criado_em DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

// Últimos 10 logs
$logs = $db->query("
    SELECT pl.mensagem, pl.status, pl.criado_em, p.numero_processo
    FROM processos_logs pl
    JOIN processos p ON p.id = pl.id_processo
    ORDER BY pl.criado_em DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

include 'layout_header.php';
?>

<!-- Cards de estatísticas -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#3b82f6,#2563eb)">
            <i class="bi bi-collection stat-icon"></i>
            <div>
                <div class="stat-value"><?= $totalGeral ?></div>
                <div class="stat-label">Total de processos</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
            <i class="bi bi-hourglass-split stat-icon"></i>
            <div>
                <div class="stat-value"><?= $totais['PENDENTE'] ?? 0 ?></div>
                <div class="stat-label">Pendentes</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#10b981,#059669)">
            <i class="bi bi-check-circle stat-icon"></i>
            <div>
                <div class="stat-value"><?= $totais['FINALIZADO'] ?? 0 ?></div>
                <div class="stat-label">Finalizados</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#ef4444,#dc2626)">
            <i class="bi bi-exclamation-triangle stat-icon"></i>
            <div>
                <div class="stat-value"><?= $totais['ERRO'] ?? 0 ?></div>
                <div class="stat-label">Com erro</div>
            </div>
        </div>
    </div>
</div>

<!-- Segunda linha de cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#06b6d4,#0891b2)">
            <i class="bi bi-arrow-repeat stat-icon"></i>
            <div>
                <div class="stat-value"><?= $totais['CONSULTANDO'] ?? 0 ?></div>
                <div class="stat-label">Consultando</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed)">
            <i class="bi bi-file-earmark-check stat-icon"></i>
            <div>
                <div class="stat-value"><?= $comAta ?></div>
                <div class="stat-label">Finalizados com ATA</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#64748b,#475569)">
            <i class="bi bi-file-earmark-x stat-icon"></i>
            <div>
                <div class="stat-value"><?= $semAta ?></div>
                <div class="stat-label">Finalizados sem ATA</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm d-flex flex-row align-items-center gap-3 p-3" style="border-radius:12px;">
            <a href="cadastrar.php" class="btn btn-primary w-100">
                <i class="bi bi-plus-lg"></i> Novo Processo
            </a>
        </div>
    </div>
</div>

<!-- Tabelas lado a lado -->
<div class="row g-3">
    <!-- Últimos processos -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between pt-3 pb-0 px-3">
                <h6 class="mb-0 fw-bold">Últimos Processos</h6>
                <a href="processos.php" class="btn btn-sm btn-outline-primary">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Processo</th>
                                <th>Status</th>
                                <th>Cadastrado</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($ultimos as $p): ?>
                        <tr style="cursor:pointer" onclick="location.href='detalhe.php?id=<?= $p['id'] ?>'">
                            <td class="font-monospace small"><?= htmlspecialchars($p['numero_processo']) ?></td>
                            <td><?= statusBadge($p['status_consulta']) ?></td>
                            <td class="text-muted small"><?= formatData($p['criado_em']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($ultimos)): ?>
                        <tr><td colspan="3" class="text-center text-muted py-3">Nenhum processo cadastrado</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimos logs -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-header bg-white border-0 pt-3 pb-0 px-3">
                <h6 class="mb-0 fw-bold">Últimos Logs</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Processo</th>
                                <th>Mensagem</th>
                                <th>Quando</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($logs as $l): ?>
                        <tr>
                            <td class="font-monospace small" style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                <?= htmlspecialchars($l['numero_processo']) ?>
                            </td>
                            <td class="small text-truncate" style="max-width:180px">
                                <?= htmlspecialchars($l['mensagem']) ?>
                            </td>
                            <td class="text-muted small"><?= formatData($l['criado_em']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($logs)): ?>
                        <tr><td colspan="3" class="text-center text-muted py-3">Nenhum log registrado</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layout_footer.php'; ?>

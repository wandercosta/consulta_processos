<?php
require_once 'config.php';
requerLogin();

$paginaAtual  = 'processos.php';
$tituloPagina = 'Processos';

$db = db();

// Filtros
$status     = $_GET['status']     ?? '';
$search     = $_GET['search']     ?? '';
$possui_ata = $_GET['possui_ata'] ?? '';
$data_de    = $_GET['data_de']    ?? '';
$data_ate   = $_GET['data_ate']   ?? '';
$pagina     = max(1, (int)($_GET['pagina'] ?? 1));
$limite     = 20;
$offset     = ($pagina - 1) * $limite;

// Monta query dinâmica
$where  = [];
$params = [];

if($status !== ''){
    $where[] = "status_consulta = ?";
    $params[] = $status;
}
if($search !== ''){
    $where[] = "numero_processo LIKE ?";
    $params[] = "%{$search}%";
}
if($possui_ata !== ''){
    $where[] = "possui_ata = ?";
    $params[] = $possui_ata;
}
if($data_de !== ''){
    $where[] = "DATE(criado_em) >= ?";
    $params[] = $data_de;
}
if($data_ate !== ''){
    $where[] = "DATE(criado_em) <= ?";
    $params[] = $data_ate;
}

$whereSql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

$countStmt = $db->prepare("SELECT COUNT(*) FROM processos {$whereSql}");
$countStmt->execute($params);
$total  = (int)$countStmt->fetchColumn();
$paginas = (int)ceil($total / $limite);

$stmt = $db->prepare("
    SELECT id, numero_processo, status_consulta, possui_ata, qtd_atas,
           data_ultima_consulta, criado_em, mensagem_erro
    FROM processos {$whereSql}
    ORDER BY criado_em DESC
    LIMIT {$limite} OFFSET {$offset}
");
$stmt->execute($params);
$processos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// URL base para paginação/filtros
$queryBase = http_build_query(array_filter([
    'status'     => $status,
    'search'     => $search,
    'possui_ata' => $possui_ata,
    'data_de'    => $data_de,
    'data_ate'   => $data_ate,
]));

include 'layout_header.php';
?>

<!-- Barra de filtros -->
<form method="get" class="filters-bar">
    <div class="row g-2 align-items-end">
        <div class="col-sm-4 col-lg-3">
            <label class="form-label form-label-sm mb-1">Número do processo</label>
            <input type="text" name="search" class="form-control form-control-sm"
                   placeholder="Buscar..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-sm-4 col-lg-2">
            <label class="form-label form-label-sm mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach(['PENDENTE','CONSULTANDO','FINALIZADO','ERRO'] as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-4 col-lg-2">
            <label class="form-label form-label-sm mb-1">Possui ATA</label>
            <select name="possui_ata" class="form-select form-select-sm">
                <option value="">Todos</option>
                <option value="S" <?= $possui_ata === 'S' ? 'selected' : '' ?>>Sim</option>
                <option value="N" <?= $possui_ata === 'N' ? 'selected' : '' ?>>Não</option>
            </select>
        </div>
        <div class="col-sm-4 col-lg-2">
            <label class="form-label form-label-sm mb-1">Cadastrado de</label>
            <input type="date" name="data_de" class="form-control form-control-sm"
                   value="<?= htmlspecialchars($data_de) ?>">
        </div>
        <div class="col-sm-4 col-lg-2">
            <label class="form-label form-label-sm mb-1">Até</label>
            <input type="date" name="data_ate" class="form-control form-control-sm"
                   value="<?= htmlspecialchars($data_ate) ?>">
        </div>
        <div class="col-sm-4 col-lg-1 d-flex gap-1">
            <button type="submit" class="btn btn-primary btn-sm flex-fill">
                <i class="bi bi-search"></i>
            </button>
            <a href="processos.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x"></i>
            </a>
        </div>
    </div>
</form>

<!-- Tabela -->
<div class="card border-0 shadow-sm" style="border-radius:12px;">
    <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between pt-3 pb-0 px-3">
        <h6 class="mb-0 fw-bold">
            <?= number_format($total, 0, ',', '.') ?> processo(s) encontrado(s)
        </h6>
        <a href="cadastrar.php" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> Novo
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Número do Processo</th>
                        <th>Status</th>
                        <th>ATA</th>
                        <th>Qtd ATAs</th>
                        <th>Última Consulta</th>
                        <th>Cadastrado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($processos as $p): ?>
                <tr>
                    <td class="text-muted small"><?= $p['id'] ?></td>
                    <td class="font-monospace"><?= htmlspecialchars($p['numero_processo']) ?></td>
                    <td><?= statusBadge($p['status_consulta']) ?></td>
                    <td><?= ataBadge($p['possui_ata']) ?></td>
                    <td><?= $p['qtd_atas'] ?? '—' ?></td>
                    <td class="text-muted small"><?= formatData($p['data_ultima_consulta']) ?></td>
                    <td class="text-muted small"><?= formatData($p['criado_em']) ?></td>
                    <td>
                        <a href="detalhe.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver detalhes">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($processos)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Nenhum processo encontrado</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginação -->
    <?php if($paginas > 1): ?>
    <div class="card-footer bg-white border-top-0 px-3 py-2">
        <nav>
            <ul class="pagination pagination-sm mb-0 justify-content-end">
                <?php if($pagina > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?pagina=<?= $pagina-1 ?>&<?= $queryBase ?>">‹</a>
                </li>
                <?php endif; ?>
                <?php for($i = max(1,$pagina-2); $i <= min($paginas,$pagina+2); $i++): ?>
                <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                    <a class="page-link" href="?pagina=<?= $i ?>&<?= $queryBase ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <?php if($pagina < $paginas): ?>
                <li class="page-item">
                    <a class="page-link" href="?pagina=<?= $pagina+1 ?>&<?= $queryBase ?>">›</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php include 'layout_footer.php'; ?>

<?php
require_once 'config.php';
requerLogin();

$paginaAtual  = 'arquivos.php';
$tituloPagina = 'Arquivos / ATAs';

$db = db();

// Filtros
$search  = $_GET['search']  ?? '';
$data_de = $_GET['data_de'] ?? '';
$data_ate = $_GET['data_ate'] ?? '';
$pagina  = max(1, (int)($_GET['pagina'] ?? 1));
$limite  = 25;
$offset  = ($pagina - 1) * $limite;

$where  = ["caminho_arquivo IS NOT NULL", "caminho_arquivo != ''"];
$params = [];

if($search !== ''){
    $where[] = "numero_processo LIKE ?";
    $params[] = "%{$search}%";
}
if($data_de !== ''){
    $where[] = "DATE(data_ultima_consulta) >= ?";
    $params[] = $data_de;
}
if($data_ate !== ''){
    $where[] = "DATE(data_ultima_consulta) <= ?";
    $params[] = $data_ate;
}

$whereSql = "WHERE " . implode(" AND ", $where);

$countStmt = $db->prepare("SELECT COUNT(*) FROM processos {$whereSql}");
$countStmt->execute($params);
$total   = (int)$countStmt->fetchColumn();
$paginas = (int)ceil($total / $limite);

$stmt = $db->prepare("
    SELECT id, numero_processo, qtd_atas, caminho_arquivo, data_ultima_consulta, status_consulta
    FROM processos {$whereSql}
    ORDER BY data_ultima_consulta DESC
    LIMIT {$limite} OFFSET {$offset}
");
$stmt->execute($params);
$arquivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$queryBase = http_build_query(array_filter([
    'search'   => $search,
    'data_de'  => $data_de,
    'data_ate' => $data_ate,
]));

include 'layout_header.php';
?>

<!-- Filtros -->
<form method="get" class="filters-bar">
    <div class="row g-2 align-items-end">
        <div class="col-sm-4 col-lg-4">
            <label class="form-label form-label-sm mb-1">Número do processo</label>
            <input type="text" name="search" class="form-control form-control-sm"
                   placeholder="Buscar..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-sm-4 col-lg-3">
            <label class="form-label form-label-sm mb-1">Finalizado de</label>
            <input type="date" name="data_de" class="form-control form-control-sm"
                   value="<?= htmlspecialchars($data_de) ?>">
        </div>
        <div class="col-sm-4 col-lg-3">
            <label class="form-label form-label-sm mb-1">Até</label>
            <input type="date" name="data_ate" class="form-control form-control-sm"
                   value="<?= htmlspecialchars($data_ate) ?>">
        </div>
        <div class="col-sm-4 col-lg-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary btn-sm flex-fill">
                <i class="bi bi-search"></i>
            </button>
            <a href="arquivos.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x"></i>
            </a>
        </div>
    </div>
</form>

<!-- Tabela de arquivos -->
<div class="card border-0 shadow-sm" style="border-radius:12px;">
    <div class="card-header bg-white border-0 pt-3 pb-0 px-3">
        <h6 class="fw-bold mb-0">
            <?= number_format($total, 0, ',', '.') ?> arquivo(s) encontrado(s)
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Número do Processo</th>
                        <th>Status</th>
                        <th>Qtd. ATAs</th>
                        <th>Caminho do Arquivo</th>
                        <th>Finalizado em</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($arquivos as $a):
                    $extensao = strtolower(pathinfo($a['caminho_arquivo'], PATHINFO_EXTENSION));
                    $icones = ['pdf'=>'bi-file-pdf','docx'=>'bi-file-word','doc'=>'bi-file-word','zip'=>'bi-file-zip'];
                    $icone = $icones[$extensao] ?? 'bi-file-earmark';
                    $existeNoDisco = file_exists($a['caminho_arquivo']);
                ?>
                <tr>
                    <td class="text-muted small"><?= $a['id'] ?></td>
                    <td class="font-monospace small"><?= htmlspecialchars($a['numero_processo']) ?></td>
                    <td><?= statusBadge($a['status_consulta']) ?></td>
                    <td><?= $a['qtd_atas'] ?? 1 ?></td>
                    <td>
                        <span class="small text-muted font-monospace" title="<?= htmlspecialchars($a['caminho_arquivo']) ?>"
                              style="display:inline-block;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;vertical-align:middle;">
                            <i class="bi <?= $icone ?> me-1"></i><?= htmlspecialchars(basename($a['caminho_arquivo'])) ?>
                        </span>
                        <?php if(!$existeNoDisco): ?>
                        <span class="badge bg-danger ms-1" title="Arquivo não encontrado no disco">
                            <i class="bi bi-exclamation-triangle"></i>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted small"><?= formatData($a['data_ultima_consulta']) ?></td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <a href="detalhe.php?id=<?= $a['id'] ?>"
                               class="btn btn-sm btn-outline-primary" title="Ver processo">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php if($existeNoDisco): ?>
                            <a href="../index.php?endpoint=download_arquivo&id=<?= $a['id'] ?>"
                               class="btn btn-sm btn-success" title="Baixar arquivo" target="_blank">
                                <i class="bi bi-download"></i>
                            </a>
                            <?php else: ?>
                            <button class="btn btn-sm btn-outline-danger" disabled title="Arquivo não encontrado no disco">
                                <i class="bi bi-x-circle"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($arquivos)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-folder2-open fs-3 d-block mb-2"></i>
                        Nenhum arquivo encontrado
                    </td>
                </tr>
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

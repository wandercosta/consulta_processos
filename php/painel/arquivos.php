<?php
require_once 'config.php';
requerLogin();

$paginaAtual  = 'arquivos.php';
$tituloPagina = 'Arquivos / ATAs';

$db = db();

// Cards de resumo
$totalArquivos = (int)$db->query("SELECT COUNT(*) FROM processos_arquivos")->fetchColumn();
$totalOk       = (int)$db->query("SELECT COUNT(*) FROM processos_arquivos WHERE download_ok = 1")->fetchColumn();
$totalFalhou   = (int)$db->query("SELECT COUNT(*) FROM processos_arquivos WHERE download_ok = 0")->fetchColumn();
$totalBytes    = (int)$db->query("SELECT COALESCE(SUM(tamanho_bytes),0) FROM processos_arquivos WHERE download_ok = 1")->fetchColumn();

// Filtros
$search   = $_GET['search']   ?? '';
$formato  = $_GET['formato']  ?? '';
$data_de  = $_GET['data_de']  ?? '';
$data_ate = $_GET['data_ate'] ?? '';
$pagina   = max(1, (int)($_GET['pagina'] ?? 1));
$limite   = 25;
$offset   = ($pagina - 1) * $limite;

$where  = [];
$params = [];

if($search !== ''){
    $where[] = "p.numero_processo LIKE ?";
    $params[] = "%{$search}%";
}
if($formato !== ''){
    $where[] = "a.formato = ?";
    $params[] = strtoupper($formato);
}
if($data_de !== ''){
    $where[] = "DATE(a.criado_em) >= ?";
    $params[] = $data_de;
}
if($data_ate !== ''){
    $where[] = "DATE(a.criado_em) <= ?";
    $params[] = $data_ate;
}

$whereSql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

$countStmt = $db->prepare("
    SELECT COUNT(*) FROM processos_arquivos a
    JOIN processos p ON p.id = a.id_processo
    {$whereSql}
");
$countStmt->execute($params);
$total   = (int)$countStmt->fetchColumn();
$paginas = (int)ceil($total / $limite);

$stmt = $db->prepare("
    SELECT a.id, a.id_processo, a.nome_arquivo, a.caminho_arquivo,
           a.formato, a.tamanho_bytes, a.texto_doc, a.indice,
           a.download_ok, a.criado_em,
           p.numero_processo
    FROM processos_arquivos a
    JOIN processos p ON p.id = a.id_processo
    {$whereSql}
    ORDER BY a.criado_em DESC
    LIMIT {$limite} OFFSET {$offset}
");
$stmt->execute($params);
$arquivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$queryBase = http_build_query(array_filter([
    'search'  => $search,
    'formato' => $formato,
    'data_de' => $data_de,
    'data_ate'=> $data_ate,
]));

include 'layout_header.php';
?>

<!-- Cards de resumo -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#3b82f6,#2563eb)">
            <i class="bi bi-files stat-icon"></i>
            <div>
                <div class="stat-value"><?= $totalArquivos ?></div>
                <div class="stat-label">Total de arquivos</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#10b981,#059669)">
            <i class="bi bi-check-circle stat-icon"></i>
            <div>
                <div class="stat-value"><?= $totalOk ?></div>
                <div class="stat-label">Downloads OK</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#ef4444,#dc2626)">
            <i class="bi bi-x-circle stat-icon"></i>
            <div>
                <div class="stat-value"><?= $totalFalhou ?></div>
                <div class="stat-label">Falhas</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed)">
            <i class="bi bi-hdd stat-icon"></i>
            <div>
                <div class="stat-value"><?= formatBytes($totalBytes) ?></div>
                <div class="stat-label">Total em disco</div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<form method="get" class="filters-bar">
    <div class="row g-2 align-items-end">
        <div class="col-sm-4 col-lg-3">
            <label class="form-label form-label-sm mb-1">Número do processo</label>
            <input type="text" name="search" class="form-control form-control-sm"
                   placeholder="Buscar..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-sm-4 col-lg-2">
            <label class="form-label form-label-sm mb-1">Formato</label>
            <select name="formato" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach(['PDF','HTML','DOCX'] as $f): ?>
                <option value="<?= $f ?>" <?= strtoupper($formato) === $f ? 'selected' : '' ?>><?= $f ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-4 col-lg-2">
            <label class="form-label form-label-sm mb-1">Baixado de</label>
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
            <a href="arquivos.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x"></i>
            </a>
        </div>
    </div>
</form>

<!-- Tabela -->
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
                        <th>Processo</th>
                        <th>Arquivo</th>
                        <th>Formato</th>
                        <th>Tamanho</th>
                        <th>Índice</th>
                        <th>Baixado em</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($arquivos as $a):
                    $existeNoDisco = !empty($a['caminho_arquivo']) && file_exists($a['caminho_arquivo']);
                    $fmt = strtolower($a['formato'] ?? '');
                    $iconesFmt = ['pdf' => 'bi-file-pdf text-danger', 'html' => 'bi-file-code text-warning', 'docx' => 'bi-file-word text-primary'];
                    $iconeFmt  = $iconesFmt[$fmt] ?? 'bi-file-earmark text-secondary';
                ?>
                <tr>
                    <td class="text-muted small"><?= $a['id'] ?></td>
                    <td class="font-monospace small">
                        <a href="detalhe.php?id=<?= $a['id_processo'] ?>" class="text-decoration-none">
                            <?= htmlspecialchars($a['numero_processo']) ?>
                        </a>
                    </td>
                    <td>
                        <span class="small font-monospace" title="<?= htmlspecialchars($a['caminho_arquivo'] ?? '') ?>">
                            <i class="bi <?= $iconeFmt ?> me-1"></i>
                            <?= htmlspecialchars($a['nome_arquivo'] ?? '—') ?>
                        </span>
                        <?php if(!empty($a['texto_doc'])): ?>
                        <br><small class="text-muted"><?= htmlspecialchars(mb_strimwidth($a['texto_doc'], 0, 60, '…')) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($a['formato']): ?>
                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($a['formato']) ?></span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="text-muted small"><?= formatBytes((int)($a['tamanho_bytes'] ?? 0)) ?></td>
                    <td class="text-center small"><?= $a['indice'] ?></td>
                    <td class="text-muted small"><?= formatData($a['criado_em']) ?></td>
                    <td class="text-center">
                        <?php if($a['download_ok']): ?>
                            <?php if($existeNoDisco): ?>
                            <span class="badge bg-success"><i class="bi bi-check me-1"></i>OK</span>
                            <?php else: ?>
                            <span class="badge bg-warning text-dark" title="Arquivo removido do disco">
                                <i class="bi bi-exclamation-triangle me-1"></i>Removido
                            </span>
                            <?php endif; ?>
                        <?php else: ?>
                        <span class="badge bg-danger"><i class="bi bi-x me-1"></i>Falhou</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <a href="detalhe.php?id=<?= $a['id_processo'] ?>"
                               class="btn btn-sm btn-outline-primary" title="Ver processo">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php if($existeNoDisco): ?>
                            <a href="../index.php?endpoint=download_arquivo&id=<?= $a['id_processo'] ?>"
                               class="btn btn-sm btn-success" title="Baixar arquivo" target="_blank">
                                <i class="bi bi-download"></i>
                            </a>
                            <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled title="Arquivo não disponível">
                                <i class="bi bi-x-circle"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($arquivos)): ?>
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
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

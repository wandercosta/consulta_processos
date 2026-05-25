<?php
function fmtBytes(int $b): string {
    if ($b >= 1073741824) return round($b/1073741824,2).' GB';
    if ($b >= 1048576)    return round($b/1048576,2).' MB';
    if ($b >= 1024)       return round($b/1024,2).' KB';
    return $b.' B';
}
$msg = $_SESSION['expurgo_msg'] ?? null;
if ($msg) unset($_SESSION['expurgo_msg']);
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg['tipo'] ?> alert-dismissible fade show" role="alert">
    <i class="bi bi-<?= $msg['tipo'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
    <?= htmlspecialchars($msg['texto']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Cards de stats -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm text-center p-3" style="border-radius:12px">
            <div class="text-muted small mb-1">Arquivos no histórico</div>
            <div class="fs-3 fw-bold text-dark"><?= number_format($stats['total']) ?></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm text-center p-3" style="border-radius:12px">
            <div class="text-muted small mb-1">Espaço liberado (total)</div>
            <div class="fs-3 fw-bold text-success"><?= fmtBytes($stats['total_bytes']) ?></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm text-center p-3" style="border-radius:12px">
            <div class="text-muted small mb-1">Processos afetados</div>
            <div class="fs-3 fw-bold text-secondary"><?= number_format($stats['processos_afetados']) ?></div>
        </div>
    </div>
</div>

<!-- Ação manual de expurgo -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:12px; border-left:4px solid #f59e0b !important">
    <div class="card-body p-4">
        <h6 class="fw-bold mb-1"><i class="bi bi-trash3 text-warning me-2"></i>Executar Expurgo Manual</h6>
        <p class="text-muted small mb-3">Remove fisicamente os arquivos baixados há mais de N dias e registra no histórico.</p>
        <form method="POST" action="<?= PAINEL_URL ?>?page=expurgo_executar"
              onsubmit="return confirm('Confirma a exclusão permanente dos arquivos elegíveis?')">
            <div class="d-flex align-items-center gap-3">
                <div class="input-group" style="max-width:220px">
                    <span class="input-group-text"><i class="bi bi-calendar-x"></i></span>
                    <input type="number" name="dias" class="form-control" value="10" min="1" max="365">
                    <span class="input-group-text">dias</span>
                </div>
                <button type="submit" class="btn btn-warning fw-semibold">
                    <i class="bi bi-trash3 me-1"></i>Executar Agora
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
    <div class="card-body p-3">
        <form method="GET" action="<?= PAINEL_URL ?>" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="expurgo">
            <div class="col-sm-4">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Número do processo" value="<?= htmlspecialchars($filtros['search']) ?>">
            </div>
            <div class="col-sm-3">
                <input type="date" name="data_de" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filtros['data_de']) ?>">
            </div>
            <div class="col-sm-3">
                <input type="date" name="data_ate" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filtros['data_ate']) ?>">
            </div>
            <div class="col-sm-2 d-flex gap-2">
                <button class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-search"></i> Filtrar
                </button>
                <a href="<?= PAINEL_URL ?>?page=expurgo" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabela -->
<div class="card border-0 shadow-sm" style="border-radius:12px">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Processo</th>
                        <th>Arquivo</th>
                        <th>Formato</th>
                        <th>Tamanho</th>
                        <th>Baixado em</th>
                        <th>Deletado em</th>
                        <th>Motivo</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($registros)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>Nenhum registro de expurgo encontrado.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                    <tr>
                        <td>
                            <a href="<?= PAINEL_URL ?>?page=detalhe&id=<?= $r['id_processo'] ?>"
                               class="font-monospace small text-decoration-none">
                                <?= htmlspecialchars($r['numero_processo'] ?: '—') ?>
                            </a>
                        </td>
                        <td class="small text-muted" style="max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                            title="<?= htmlspecialchars($r['caminho_arquivo']) ?>">
                            <i class="bi bi-file-earmark me-1"></i><?= htmlspecialchars($r['nome_arquivo'] ?: basename($r['caminho_arquivo'])) ?>
                        </td>
                        <td>
                            <?php if ($r['formato']): ?>
                            <span class="badge bg-secondary"><?= strtoupper(htmlspecialchars($r['formato'])) ?></span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td class="small"><?= $r['tamanho_bytes'] ? fmtBytes((int)$r['tamanho_bytes']) : '—' ?></td>
                        <td class="small text-muted"><?= $r['baixado_em'] ? date('d/m/Y H:i', strtotime($r['baixado_em'])) : '—' ?></td>
                        <td class="small"><?= date('d/m/Y H:i', strtotime($r['deletado_em'])) ?></td>
                        <td><span class="badge bg-light text-dark border small"><?= htmlspecialchars($r['motivo']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <?php if ($paginas > 1): ?>
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
            <small class="text-muted"><?= number_format($total) ?> registro(s)</small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($p = 1; $p <= $paginas; $p++): ?>
                    <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
                        <a class="page-link" href="<?= PAINEL_URL ?>?page=expurgo&pagina=<?= $p ?>&<?= $queryBase ?>">
                            <?= $p ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

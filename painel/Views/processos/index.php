<?php
// Variáveis injetadas pelo ProcessoController:
// $processos, $total, $paginas, $pagina, $limite, $filtros, $queryBase

$status     = $filtros['status']     ?? '';
$search     = $filtros['search']     ?? '';
$possui_ata = $filtros['possui_ata'] ?? '';
$data_de    = $filtros['data_de']    ?? '';
$data_ate   = $filtros['data_ate']   ?? '';
?>

<!-- Barra de filtros -->
<form method="get" action="<?= PAINEL_URL ?>" class="filters-bar">
    <input type="hidden" name="page" value="processos">
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
                <?php foreach (['PENDENTE','CONSULTANDO','FINALIZADO COM ATA','FINALIZADO SEM ATA','ERRO'] as $s): ?>
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
            <a href="<?= PAINEL_URL ?>?page=processos" class="btn btn-outline-secondary btn-sm">
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
        <a href="<?= PAINEL_URL ?>?page=cadastrar" class="btn btn-sm btn-primary">
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
                <?php foreach ($processos as $p): ?>
                <tr>
                    <td class="text-muted small"><?= $p['id'] ?></td>
                    <td class="font-monospace"><?= htmlspecialchars($p['numero_processo']) ?></td>
                    <td><?= statusBadge($p['status_consulta']) ?></td>
                    <td><?= ataBadge($p['possui_ata']) ?></td>
                    <td><?= $p['qtd_atas'] ?? '—' ?></td>
                    <td class="text-muted small"><?= formatData($p['data_ultima_consulta']) ?></td>
                    <td class="text-muted small"><?= formatData($p['criado_em']) ?></td>
                    <td>
                        <a href="<?= PAINEL_URL ?>?page=detalhe&id=<?= $p['id'] ?>"
                           class="btn btn-sm btn-outline-primary" title="Ver detalhes">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($processos)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Nenhum processo encontrado</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginação -->
    <?php if ($paginas > 1): ?>
    <div class="card-footer bg-white border-top-0 px-3 py-2">
        <nav>
            <ul class="pagination pagination-sm mb-0 justify-content-end">
                <?php if ($pagina > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= PAINEL_URL ?>?page=processos&pagina=<?= $pagina - 1 ?>&<?= $queryBase ?>">‹</a>
                </li>
                <?php endif; ?>
                <?php for ($i = max(1, $pagina - 2); $i <= min($paginas, $pagina + 2); $i++): ?>
                <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                    <a class="page-link" href="<?= PAINEL_URL ?>?page=processos&pagina=<?= $i ?>&<?= $queryBase ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <?php if ($pagina < $paginas): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= PAINEL_URL ?>?page=processos&pagina=<?= $pagina + 1 ?>&<?= $queryBase ?>">›</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

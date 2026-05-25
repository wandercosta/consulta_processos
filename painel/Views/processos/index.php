<?php
// Variáveis injetadas pelo ProcessoController:
// $processos, $total, $paginas, $pagina, $limite, $filtros, $queryBase

$status     = $filtros['status']     ?? '';
$search     = $filtros['search']     ?? '';
$possui_ata = $filtros['possui_ata'] ?? '';
$data_de    = $filtros['data_de']    ?? '';
$data_ate   = $filtros['data_ate']   ?? '';

// Verifica se há ESGOTADOS na página atual (para exibir coluna de checkbox)
$temEsgotado = !empty(array_filter($processos, fn($p) => $p['status_consulta'] === 'ESGOTADO'));
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
                <?php foreach (['PENDENTE','CONSULTANDO','FINALIZADO COM ATA','FINALIZADO SEM ATA','ESGOTADO','ERRO','NÃO COMPATÍVEL','CANCELADO'] as $s): ?>
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

<!-- Formulário de ações em lote (envolve a tabela) -->
<form method="post" action="<?= PAINEL_URL ?>?page=reativar_lote" id="form-lote">
    <input type="hidden" name="volta" value="<?= htmlspecialchars('processos&' . http_build_query(array_filter($filtros))) ?>">

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
                        <?php if ($temEsgotado): ?>
                        <th style="width:36px" class="ps-3">
                            <input type="checkbox" class="form-check-input" id="check-todos"
                                   title="Marcar todos os ESGOTADOS">
                        </th>
                        <?php endif; ?>
                        <th>#</th>
                        <th>Número do Processo</th>
                        <th>Tribunal / Tipo</th>
                        <th>Status</th>
                        <th>ATA</th>
                        <th>Qtd ATAs</th>
                        <th>Última Consulta</th>
                        <th>Cadastrado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($processos as $p):
                    $esgotado = $p['status_consulta'] === 'ESGOTADO';
                ?>
                <tr class="<?= $esgotado ? 'row-esgotado' : '' ?>">
                    <?php if ($temEsgotado): ?>
                    <td class="ps-3">
                        <?php if ($esgotado): ?>
                        <input type="checkbox" class="form-check-input chk-processo"
                               name="ids[]" value="<?= $p['id'] ?>"
                               onchange="atualizarBarra()">
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td class="text-muted small"><?= $p['id'] ?></td>
                    <td class="font-monospace small"><?= htmlspecialchars($p['numero_processo']) ?></td>
                    <td>
                        <span class="badge bg-light text-dark border me-1"><?= htmlspecialchars($p['tribunal'] ?? '—') ?></span>
                        <?= tipoBadge($p['tipo_sistema'] ?? null) ?>
                    </td>
                    <td><?= statusBadge($p['status_consulta']) ?></td>
                    <td><?= ataBadge($p['possui_ata']) ?></td>
                    <td><?= $p['qtd_atas'] ?? '—' ?></td>
                    <td class="text-muted small"><?= formatData($p['data_ultima_consulta']) ?></td>
                    <td class="text-muted small"><?= formatData($p['criado_em']) ?></td>
                    <td class="text-nowrap">
                        <a href="<?= PAINEL_URL ?>?page=detalhe&id=<?= $p['id'] ?>"
                           class="btn btn-sm btn-outline-primary" title="Ver detalhes">
                            <i class="bi bi-eye"></i>
                        </a>
                        <?php if ($p['status_consulta'] === 'CANCELADO'): ?>
                        <form method="post" action="<?= PAINEL_URL ?>?page=recolocar_processo"
                              class="d-inline" onsubmit="return confirm('Recolocar na fila?')">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button class="btn btn-sm btn-outline-success" title="Recolocar na fila">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                        </form>
                        <?php elseif ($esgotado): ?>
                        <button type="button" class="btn btn-sm btn-outline-warning"
                                title="Reativar este processo"
                                onclick="reativarUm(<?= $p['id'] ?>)">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                        <?php elseif (in_array($p['status_consulta'], ['PENDENTE','FINALIZADO SEM ATA','ERRO','NÃO COMPATÍVEL'])): ?>
                        <form method="post" action="<?= PAINEL_URL ?>?page=cancelar_processo"
                              class="d-inline" onsubmit="return confirm('Cancelar este processo?')">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger" title="Cancelar">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($processos)): ?>
                <tr><td colspan="<?= $temEsgotado ? 10 : 9 ?>" class="text-center text-muted py-4">Nenhum processo encontrado</td></tr>
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

</form><!-- /form-lote -->

<?php if ($temEsgotado): ?>
<!-- Barra de ação flutuante (aparece ao selecionar) -->
<div id="barra-lote" class="d-none"
     style="position:fixed;bottom:24px;left:50%;transform:translateX(-50%);
            background:#1e293b;color:#fff;border-radius:12px;padding:12px 20px;
            display:flex;align-items:center;gap:16px;box-shadow:0 8px 32px rgba(0,0,0,.35);
            z-index:1050;min-width:340px;transition:opacity .2s">
    <span><strong id="label-qtd">0</strong> processo(s) selecionado(s)</span>
    <button type="button" class="btn btn-warning btn-sm fw-semibold"
            onclick="submeterLote()">
        <i class="bi bi-arrow-counterclockwise me-1"></i>Reativar selecionados
    </button>
    <button type="button" class="btn btn-outline-light btn-sm"
            onclick="desmarcarTodos()">
        <i class="bi bi-x"></i>
    </button>
</div>

<script>
const checkTodos = document.getElementById('check-todos');
const barra      = document.getElementById('barra-lote');
const labelQtd   = document.getElementById('label-qtd');

// "Marcar todos" — afeta apenas os checkboxes de ESGOTADO
checkTodos.addEventListener('change', function () {
    document.querySelectorAll('.chk-processo').forEach(c => c.checked = this.checked);
    atualizarBarra();
});

function atualizarBarra() {
    const marcados = document.querySelectorAll('.chk-processo:checked').length;
    const total    = document.querySelectorAll('.chk-processo').length;
    labelQtd.textContent = marcados;
    barra.classList.toggle('d-none', marcados === 0);
    // Sincroniza o check-todos (indeterminate se parcial)
    checkTodos.indeterminate = marcados > 0 && marcados < total;
    checkTodos.checked = marcados === total && total > 0;
}

function desmarcarTodos() {
    document.querySelectorAll('.chk-processo').forEach(c => c.checked = false);
    checkTodos.checked      = false;
    checkTodos.indeterminate = false;
    atualizarBarra();
}

function submeterLote() {
    const qtd = document.querySelectorAll('.chk-processo:checked').length;
    if (qtd === 0) return;
    if (!confirm('Reativar ' + qtd + ' processo(s) ESGOTADO(s)? Eles voltarão para PENDENTE com tentativas zeradas.')) return;
    document.getElementById('form-lote').submit();
}

// Botão de reativar individual — injeta um input hidden e submete
function reativarUm(id) {
    if (!confirm('Reativar este processo? Voltará para PENDENTE com tentativas zeradas.')) return;
    const form = document.getElementById('form-lote');
    const inp  = document.createElement('input');
    inp.type   = 'hidden';
    inp.name   = 'ids[]';
    inp.value  = id;
    // Desmarca os outros para não reativar junto
    document.querySelectorAll('.chk-processo').forEach(c => c.checked = false);
    form.appendChild(inp);
    form.submit();
}
</script>
<?php endif; ?>

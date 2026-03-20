<?php
// Variáveis injetadas pelo ConfiguracaoController:
// $config, $sucesso, $erro, $extsAtuais, $extensoesDisponiveis
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">

        <?php if (!empty($sucesso)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($sucesso) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (!empty($erro)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($erro) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="post" action="<?= PAINEL_URL ?>?page=configuracoes">

            <!-- Busca automática -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
                <div class="card-header bg-white border-0 pt-3 pb-0 px-4">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-arrow-clockwise text-primary me-2"></i>Busca Automática
                    </h6>
                    <p class="text-muted small mb-0 mt-1">Controla o comportamento do robô de reprocessamento.</p>
                </div>
                <div class="card-body px-4 py-3">

                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            Número máximo de tentativas
                            <span class="badge bg-primary ms-2" id="badge-tentativas">
                                <?= (int)($config['max_tentativas'] ?? 10) ?>
                            </span>
                        </label>
                        <input type="range" class="form-range" name="max_tentativas"
                               id="range-tentativas"
                               min="1" max="50" step="1"
                               value="<?= (int)($config['max_tentativas'] ?? 10) ?>"
                               oninput="document.getElementById('badge-tentativas').textContent = this.value;
                                        document.getElementById('input-tentativas').value = this.value;">
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <small class="text-muted">1 tentativa</small>
                            <div class="d-flex align-items-center gap-2">
                                <small class="text-muted">ou digite:</small>
                                <input type="number" id="input-tentativas" class="form-control form-control-sm"
                                       style="width:80px"
                                       min="1" max="50" value="<?= (int)($config['max_tentativas'] ?? 10) ?>"
                                       oninput="document.getElementById('range-tentativas').value = this.value;
                                                document.getElementById('badge-tentativas').textContent = this.value;">
                            </div>
                            <small class="text-muted">50 tentativas</small>
                        </div>
                        <div class="form-text mt-2">
                            <i class="bi bi-info-circle me-1"></i>
                            Após atingir esse número de tentativas sem encontrar ATA, o processo muda para
                            <span class="badge bg-dark"><i class="bi bi-slash-circle-fill me-1"></i>ESGOTADO</span>
                            e sai da fila automática permanentemente.
                        </div>
                    </div>

                </div>
            </div>

            <!-- Extensões aceitas -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
                <div class="card-header bg-white border-0 pt-3 pb-0 px-4">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-file-earmark-check text-success me-2"></i>Extensões de Arquivo Aceitas
                    </h6>
                    <p class="text-muted small mb-0 mt-1">
                        O robô só considerará arquivos com as extensões selecionadas ao baixar ATAs.
                    </p>
                </div>
                <div class="card-body px-4 py-3">

                    <div class="row g-2 mb-3">
                        <?php foreach ($extensoesDisponiveis as $ext): ?>
                        <div class="col-6 col-sm-4 col-md-3">
                            <div class="form-check form-check-custom border rounded p-2 <?= in_array($ext, $extsAtuais) ? 'border-primary bg-primary bg-opacity-10' : '' ?>"
                                 id="box-<?= $ext ?>">
                                <input class="form-check-input" type="checkbox"
                                       name="extensoes[]" value="<?= $ext ?>"
                                       id="ext-<?= $ext ?>"
                                       <?= in_array($ext, $extsAtuais) ? 'checked' : '' ?>
                                       onchange="toggleBox('<?= $ext ?>', this.checked)">
                                <label class="form-check-label fw-semibold ms-1" for="ext-<?= $ext ?>">
                                    <i class="bi bi-file-earmark me-1 text-muted"></i><?= strtoupper($ext) ?>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-muted">Outras extensões (separe por vírgula)</label>
                        <input type="text" class="form-control form-control-sm" id="ext-outras"
                               placeholder="ex: odt, rtf"
                               value="<?= htmlspecialchars(implode(', ', array_diff($extsAtuais, $extensoesDisponiveis))) ?>">
                        <div class="form-text">Pressione <kbd>Enter</kbd> ou clique em Salvar para incluir.</div>
                    </div>

                    <div class="form-text">
                        <i class="bi bi-info-circle me-1"></i>
                        Selecionadas: <strong id="label-ext-selecionadas"><?= implode(', ', $extsAtuais) ?: '—' ?></strong>
                    </div>

                </div>
            </div>

            <!-- Botão salvar -->
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary" onclick="prepararExtras()">
                    <i class="bi bi-save me-1"></i>Salvar configurações
                </button>
                <a href="<?= PAINEL_URL ?>?page=configuracoes" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Restaurar
                </a>
            </div>

        </form>

    </div>
</div>

<script>
// Destaca o card da extensão quando marcado/desmarcado
function toggleBox(ext, checked) {
    const box = document.getElementById('box-' + ext);
    box.classList.toggle('border-primary', checked);
    box.classList.toggle('bg-primary', checked);
    box.classList.toggle('bg-opacity-10', checked);
    atualizarLabelExt();
}

// Atualiza o label de extensões selecionadas
function atualizarLabelExt() {
    const checks = document.querySelectorAll('input[name="extensoes[]"]:checked');
    const nomes  = Array.from(checks).map(c => c.value.toUpperCase());
    document.getElementById('label-ext-selecionadas').textContent = nomes.join(', ') || '—';
}

// Antes de submeter: pega extensões digitadas manualmente e cria checkboxes hidden
function prepararExtras() {
    const outras = document.getElementById('ext-outras').value;
    if (!outras.trim()) return;
    const exts = outras.split(',').map(e => e.trim().toLowerCase()).filter(Boolean);
    const form = document.querySelector('form');
    exts.forEach(ext => {
        const el = document.querySelector('input[name="extensoes[]"][value="' + ext + '"]');
        if (!el) {
            const hidden = document.createElement('input');
            hidden.type  = 'hidden';
            hidden.name  = 'extensoes[]';
            hidden.value = ext;
            form.appendChild(hidden);
        }
    });
}
</script>

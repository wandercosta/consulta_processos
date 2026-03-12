<?php
// Variáveis injetadas pelo ProcessoController:
// $erro, $sucesso
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">
                    <i class="bi bi-plus-circle text-primary me-2"></i>Novo Processo
                </h5>

                <?php if (!empty($erro)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($erro) ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($sucesso)): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i><?= $sucesso ?>
                    <div class="mt-2">
                        <a href="<?= PAINEL_URL ?>?page=cadastrar" class="btn btn-sm btn-success me-2">Cadastrar outro</a>
                        <a href="<?= PAINEL_URL ?>?page=processos" class="btn btn-sm btn-outline-secondary">Ver todos</a>
                    </div>
                </div>
                <?php else: ?>
                <form method="post" action="<?= PAINEL_URL ?>?page=cadastrar">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tribunal</label>
                        <select name="tribunal" class="form-select" required>
                            <?php foreach ($tribunais as $sigla => $label): ?>
                            <option value="<?= htmlspecialchars($sigla) ?>"
                                <?= (($_POST['tribunal'] ?? $sigla) === $sigla ? 'selected' : '') ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Número do Processo</label>
                        <input type="text" name="numero_processo"
                               class="form-control font-monospace"
                               placeholder="Ex: 5053546-33.2022.8.13.0079"
                               value="<?= htmlspecialchars($_POST['numero_processo'] ?? '') ?>"
                               autofocus required>
                        <div class="form-text">Informe o número único do processo judicial.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cód. API / Integração <span class="text-muted fw-normal">(opcional)</span></label>
                        <input type="text" name="cod_api"
                               class="form-control font-monospace"
                               placeholder="Ex: ORD-2025-001"
                               value="<?= htmlspecialchars($_POST['cod_api'] ?? '') ?>">
                        <div class="form-text">Identificador do seu sistema enviado no webhook ao finalizar.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Data do Ato <span class="text-muted fw-normal">(opcional)</span></label>
                        <input type="date" name="data_ato"
                               class="form-control"
                               value="<?= htmlspecialchars($_POST['data_ato'] ?? '') ?>">
                        <div class="form-text">
                            O robô só buscará a ATA após essa data, e retornará apenas documentos com data igual ou posterior.
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Cadastrar
                        </button>
                        <a href="<?= PAINEL_URL ?>?page=processos" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-3" style="border-radius:12px;">
            <div class="card-body p-3">
                <h6 class="fw-bold mb-2 text-muted">
                    <i class="bi bi-list-ul me-2"></i>Cadastro em lote via API
                </h6>
                <p class="small text-muted mb-2">Você pode cadastrar múltiplos processos via API:</p>
                <pre class="bg-light rounded p-2 small mb-2" style="font-size:.78rem;overflow-x:auto">POST <?= rtrim(str_replace('/index.php', '', API_DOWNLOAD_URL), '/') ?>/?endpoint=cadastrar_processo
Authorization: Bearer CLAUDE_AUTOMACAO_123
Content-Type: application/json

{
  "numero_processo": "5003854-46.2025.8.13.0407",
  "tribunal": "MG",
  "data_ato": "2025-03-01"
}</pre>
                <p class="small text-muted mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Tipo detectado automaticamente pelo 1º dígito do número (<strong>MG</strong>):
                    <strong>5</strong>=PJE &bull; <strong>0/1</strong>=EPROC &bull; <strong>2</strong>=PROCON.
                    Outros tribunais terão suas próprias regras de classificação.<br>
                    <code>tribunal</code> e <code>data_ato</code> são opcionais.
                </p>
            </div>
        </div>
    </div>
</div>

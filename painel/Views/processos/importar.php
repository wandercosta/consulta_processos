<?php
/**
 * View: Importar processos via planilha Excel / CSV
 *
 * Variáveis injetadas:
 *   $resultados  — array de resultados por linha (null se ainda não importou)
 *   $erroUpload  — string de erro geral (null se ok)
 */

// Contadores para o resumo
$total      = $resultados ? count($resultados) : 0;
$importados = $resultados ? count(array_filter($resultados, fn($r) => $r['status'] === 'importado'))  : 0;
$duplicados = $resultados ? count(array_filter($resultados, fn($r) => $r['status'] === 'duplicado')) : 0;
$erros      = $resultados ? count(array_filter($resultados, fn($r) => $r['status'] === 'erro'))      : 0;
$ignorados  = $resultados ? count(array_filter($resultados, fn($r) => $r['status'] === 'ignorada'))  : 0;
?>

<div class="row">

    <!-- ── Formulário de upload ───────────────────────────────────────────────── -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm" style="border-radius:12px">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-1"><i class="bi bi-file-earmark-excel text-success me-2"></i>Importar Planilha</h6>
                <p class="text-muted small mb-3">
                    Envie um arquivo <strong>.xlsx</strong> ou <strong>.csv</strong> com os processos a cadastrar.
                    Linhas duplicadas são ignoradas automaticamente.
                </p>

                <?php if ($erroUpload): ?>
                <div class="alert alert-danger py-2 small">
                    <i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($erroUpload) ?>
                </div>
                <?php endif; ?>

                <form method="post" action="<?= PAINEL_URL ?>?page=importar_processar"
                      enctype="multipart/form-data">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Arquivo (.xlsx ou .csv)</label>
                        <input type="file" name="planilha" class="form-control"
                               accept=".xlsx,.csv" required>
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-upload me-1"></i>Importar
                    </button>
                </form>
            </div>
        </div>

        <!-- Instruções de formato -->
        <div class="card border-0 shadow-sm mt-3" style="border-radius:12px">
            <div class="card-body p-3">
                <h6 class="fw-semibold small mb-2"><i class="bi bi-info-circle text-primary me-1"></i>Formato esperado</h6>
                <p class="small text-muted mb-2">A planilha deve conter as colunas abaixo (com ou sem cabeçalho, em qualquer ordem):</p>
                <table class="table table-sm table-bordered small mb-2">
                    <thead class="table-light">
                        <tr>
                            <th>Coluna</th>
                            <th>Obrigatório</th>
                            <th>Exemplo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><code>processo</code></td><td class="text-center">✅</td><td class="font-monospace small">5003854-46.2025.8.13.0407</td></tr>
                        <tr><td><code>data</code></td><td class="text-center">—</td><td>22/04/2025</td></tr>
                        <tr><td><code>uf</code></td><td class="text-center">✅</td><td>MG</td></tr>
                        <tr><td><code>idapi</code></td><td class="text-center">—</td><td>ORD-2025-001</td></tr>
                    </tbody>
                </table>
                <div class="text-muted small">
                    <i class="bi bi-calendar2 me-1"></i>Data aceita: <code>DD/MM/AAAA</code> ou <code>AAAA-MM-DD</code><br>
                    <i class="bi bi-columns me-1"></i>CSV: separe por <code>;</code> ou <code>,</code>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Resultados ─────────────────────────────────────────────────────────── -->
    <div class="col-lg-7">
        <?php if ($resultados !== null): ?>

        <!-- Resumo -->
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-3" style="border-radius:10px">
                    <div class="fs-4 fw-bold text-success"><?= $importados ?></div>
                    <div class="small text-muted">Importados</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-3" style="border-radius:10px">
                    <div class="fs-4 fw-bold text-warning"><?= $duplicados ?></div>
                    <div class="small text-muted">Duplicados</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-3" style="border-radius:10px">
                    <div class="fs-4 fw-bold text-danger"><?= $erros ?></div>
                    <div class="small text-muted">Erros</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-3" style="border-radius:10px">
                    <div class="fs-4 fw-bold text-secondary"><?= $ignorados ?></div>
                    <div class="small text-muted">Ignorados</div>
                </div>
            </div>
        </div>

        <!-- Tabela de resultados -->
        <div class="card border-0 shadow-sm" style="border-radius:12px">
            <div class="card-body p-0">
                <div style="max-height:520px;overflow-y:auto">
                    <table class="table table-sm table-hover mb-0 small">
                        <thead class="sticky-top bg-white">
                            <tr>
                                <th class="ps-3" style="width:50px">Linha</th>
                                <th>Processo</th>
                                <th style="width:110px">Status</th>
                                <th>Mensagem</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($resultados as $r): ?>
                        <tr>
                            <td class="ps-3 text-muted"><?= $r['linha'] ?></td>
                            <td class="font-monospace"><?= htmlspecialchars($r['processo']) ?></td>
                            <td>
                                <?php match($r['status']) {
                                    'importado'  => print('<span class="badge bg-success">Importado</span>'),
                                    'duplicado'  => print('<span class="badge bg-warning text-dark">Duplicado</span>'),
                                    'erro'       => print('<span class="badge bg-danger">Erro</span>'),
                                    'ignorada'   => print('<span class="badge bg-secondary">Ignorado</span>'),
                                    default      => print('<span class="badge bg-light text-dark">' . $r['status'] . '</span>'),
                                }; ?>
                            </td>
                            <td class="text-muted"><?= htmlspecialchars($r['mensagem']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php elseif (!$erroUpload): ?>
        <div class="d-flex align-items-center justify-content-center h-100 text-muted" style="min-height:200px">
            <div class="text-center">
                <i class="bi bi-file-earmark-arrow-up fs-1 d-block mb-2 opacity-25"></i>
                Envie um arquivo para ver os resultados aqui
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php
// Variáveis injetadas pelo WebhookController:
// $logs, $total, $paginas, $pagina, $config
?>

<ul class="nav nav-tabs mb-4" id="webhookTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-logs">
            <i class="bi bi-list-ul me-1"></i>Histórico de Envios
            <?php if ($total > 0): ?>
            <span class="badge bg-secondary ms-1"><?= $total ?></span>
            <?php endif; ?>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-config">
            <i class="bi bi-gear me-1"></i>Configuração
            <?php if (empty($config['url'])): ?>
            <span class="badge bg-warning text-dark ms-1">Não configurado</span>
            <?php elseif (!$config['ativo']): ?>
            <span class="badge bg-secondary ms-1">Inativo</span>
            <?php else: ?>
            <span class="badge bg-success ms-1">Ativo</span>
            <?php endif; ?>
        </button>
    </li>
</ul>

<div class="tab-content">

    <!-- ── HISTÓRICO ──────────────────────────────────────────────────────────── -->
    <div class="tab-pane fade show active" id="tab-logs">
        <div class="card border-0 shadow-sm" style="border-radius:12px">
            <div class="card-body p-0">
                <?php if (empty($logs)): ?>
                <p class="text-muted text-center py-5">Nenhum envio registrado ainda.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 small">
                        <thead>
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Processo</th>
                                <th>Cód. API</th>
                                <th>Status</th>
                                <th>HTTP</th>
                                <th>Enviado em</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($logs as $log): ?>
                        <?php
                            $payload = json_decode($log['payload'], true) ?? [];
                            $statusEvento = $payload['evento'] ?? '—';
                        ?>
                        <tr>
                            <td class="ps-3 text-muted"><?= $log['id'] ?></td>
                            <td class="font-monospace"><?= htmlspecialchars($log['numero_processo']) ?></td>
                            <td class="text-muted"><?= htmlspecialchars($log['cod_api'] ?? '—') ?></td>
                            <td><?= statusBadge($statusEvento) ?></td>
                            <td>
                                <?php if ($log['sucesso']): ?>
                                <span class="badge bg-success"><?= $log['status_http'] ?></span>
                                <?php elseif ($log['status_http']): ?>
                                <span class="badge bg-danger"><?= $log['status_http'] ?></span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Falha</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= formatData($log['enviado_em']) ?></td>
                            <td class="text-end pe-3">
                                <!-- Payload modal -->
                                <button class="btn btn-sm btn-outline-secondary me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalPayload"
                                        data-payload="<?= htmlspecialchars(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?>"
                                        data-resposta="<?= htmlspecialchars($log['resposta'] ?? '') ?>"
                                        title="Ver payload">
                                    <i class="bi bi-code-slash"></i>
                                </button>
                                <!-- Reenvio -->
                                <form method="post" action="<?= PAINEL_URL ?>?page=webhook_reenviar" class="d-inline"
                                      onsubmit="return confirm('Reenviar este webhook?')">
                                    <input type="hidden" name="id" value="<?= $log['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-primary" title="Reenviar">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <?php if ($paginas > 1): ?>
                <div class="d-flex justify-content-center py-3">
                    <?php for ($i = 1; $i <= $paginas; $i++): ?>
                    <a href="<?= PAINEL_URL ?>?page=webhook&pagina=<?= $i ?>"
                       class="btn btn-sm <?= $i === $pagina ? 'btn-primary' : 'btn-outline-secondary' ?> me-1">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── CONFIGURAÇÃO ───────────────────────────────────────────────────────── -->
    <div class="tab-pane fade" id="tab-config">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm" style="border-radius:12px">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-send text-primary me-2"></i>Configuração do Webhook</h6>
                        <p class="small text-muted mb-4">
                            Ao finalizar uma busca (com ATA, sem ATA, erro ou não compatível),
                            o sistema dispara um POST para a URL abaixo com o payload JSON do processo.
                        </p>

                        <form method="post" action="<?= PAINEL_URL ?>?page=webhook_config">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">URL de destino</label>
                                <input type="url" name="url" class="form-control font-monospace"
                                       placeholder="https://meuapp.com/webhook/processos"
                                       value="<?= htmlspecialchars($config['url'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Secret <span class="text-muted fw-normal">(opcional)</span></label>
                                <input type="text" name="secret" class="form-control font-monospace"
                                       placeholder="chave-secreta"
                                       value="<?= htmlspecialchars($config['secret'] ?? '') ?>">
                                <div class="form-text">Enviado no header <code>X-Webhook-Secret</code> para verificar autenticidade.</div>
                            </div>
                            <div class="mb-4 form-check">
                                <input type="checkbox" name="ativo" id="ativo" class="form-check-input"
                                       <?= !empty($config['ativo']) ? 'checked' : '' ?>>
                                <label for="ativo" class="form-check-label">Webhook ativo</label>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Salvar
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Exemplo de payload -->
                <div class="card border-0 shadow-sm mt-3" style="border-radius:12px">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-2 text-muted small"><i class="bi bi-code-slash me-1"></i>Exemplo de payload enviado</h6>
                        <pre class="bg-light rounded p-2 mb-0 small" style="font-size:.78rem"><?= htmlspecialchars(json_encode([
    'evento'          => 'FINALIZADO COM ATA',
    'id_integracao'   => 'ORD-2025-001',
    'numero_processo' => '5003854-46.2025.8.13.0407',
    'status'          => 'FINALIZADO COM ATA',
    'tribunal'        => 'MG',
    'tipo_sistema'    => 'PJE',
    'qtd_atas'        => 1,
    'data_consulta'   => '2025-03-12 10:30:00',
    'arquivos'        => [[
        'id'            => 42,
        'nome'          => 'ata_audiencia.pdf',
        'formato'       => 'PDF',
        'tamanho_bytes' => 102400,
        'url'           => 'https://meuservidor.com/processos_api/api/?endpoint=download_arquivo_id&id=42',
    ]],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal payload -->
<div class="modal fade" id="modalPayload" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Payload / Resposta</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 class="small text-muted fw-semibold">PAYLOAD ENVIADO</h6>
                <pre id="payloadContent" class="bg-light rounded p-3 small" style="max-height:300px;overflow:auto"></pre>
                <h6 class="small text-muted fw-semibold mt-3">RESPOSTA RECEBIDA</h6>
                <pre id="respostaContent" class="bg-light rounded p-3 small" style="max-height:200px;overflow:auto"></pre>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('modalPayload').addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    document.getElementById('payloadContent').textContent  = btn.dataset.payload  || '—';
    document.getElementById('respostaContent').textContent = btn.dataset.resposta || '—';
});
<?php if (!empty($config['url'])): ?>
// Abre na aba config se salvou
<?php if (isset($_GET['saved'])): ?>
document.querySelector('[data-bs-target="#tab-config"]').click();
<?php endif; ?>
<?php endif; ?>
</script>

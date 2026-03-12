<?php
// Variáveis injetadas pelo ProcessoController:
// $processo, $logs, $arquivos
?>

<div class="mb-3">
    <a href="<?= PAINEL_URL ?>?page=processos" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<div class="row g-3">
    <!-- Coluna esquerda: dados + arquivos -->
    <div class="col-lg-5">

        <!-- Dados do processo -->
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-header bg-white border-0 pt-3 pb-0 px-3">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-journal-text text-primary me-2"></i>Dados do Processo
                </h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th class="text-muted fw-normal w-40">ID</th>
                        <td><?= $processo['id'] ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Número</th>
                        <td class="font-monospace"><?= htmlspecialchars($processo['numero_processo']) ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Status</th>
                        <td><?= statusBadge($processo['status_consulta']) ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Possui ATA</th>
                        <td><?= ataBadge($processo['possui_ata']) ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Qtd. ATAs</th>
                        <td><?= $processo['qtd_atas'] ?? '—' ?></td>
                    </tr>
                    <?php if (!empty($processo['data_ato'])): ?>
                    <tr>
                        <th class="text-muted fw-normal">Data do Ato</th>
                        <td>
                            <?= date('d/m/Y', strtotime($processo['data_ato'])) ?>
                            <?php if ($processo['data_ato'] > date('Y-m-d')): ?>
                            <span class="badge bg-warning text-dark ms-1" title="Busca aguardando esta data">
                                <i class="bi bi-clock me-1"></i>Aguardando
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th class="text-muted fw-normal">Cadastrado em</th>
                        <td><?= formatData($processo['criado_em']) ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Última consulta</th>
                        <td><?= formatData($processo['data_ultima_consulta']) ?></td>
                    </tr>
                    <?php if (!empty($processo['mensagem_erro'])): ?>
                    <tr>
                        <th class="text-muted fw-normal">Erro</th>
                        <td class="text-danger small"><?= htmlspecialchars($processo['mensagem_erro']) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- Arquivos / ATAs -->
        <div class="card border-0 shadow-sm mt-3" style="border-radius:12px;">
            <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between pt-3 pb-0 px-3">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-file-earmark-arrow-down text-success me-2"></i>Arquivos / ATAs
                </h6>
                <span class="badge bg-secondary"><?= count($arquivos) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($arquivos)): ?>
                <div class="text-center text-muted py-4 px-3">
                    <i class="bi bi-file-earmark-x fs-3 d-block mb-1"></i>
                    <small>Nenhum arquivo registrado para este processo</small>
                </div>
                <?php else: ?>
                <ul class="list-group list-group-flush" style="border-radius:0 0 12px 12px;overflow:hidden;">
                    <?php foreach ($arquivos as $a):
                        $existeNoDisco = !empty($a['caminho_arquivo']) && file_exists(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $a['caminho_arquivo']));
                        $fmt = strtolower($a['formato'] ?? '');
                        $iconesFmt = ['pdf' => 'bi-file-pdf text-danger', 'html' => 'bi-file-code text-warning', 'docx' => 'bi-file-word text-primary'];
                        $iconeFmt  = $iconesFmt[$fmt] ?? 'bi-file-earmark text-secondary';
                    ?>
                    <li class="list-group-item border-0 px-3 py-2">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div class="flex-grow-1" style="min-width:0">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="bi <?= $iconeFmt ?>"></i>
                                    <span class="small font-monospace text-truncate" title="<?= htmlspecialchars($a['nome_arquivo'] ?? '') ?>">
                                        <?= htmlspecialchars($a['nome_arquivo'] ?? '—') ?>
                                    </span>
                                </div>
                                <?php if (!empty($a['texto_doc'])): ?>
                                <div class="small text-muted text-truncate">
                                    <?= htmlspecialchars(mb_strimwidth($a['texto_doc'], 0, 80, '…')) ?>
                                </div>
                                <?php endif; ?>
                                <div class="d-flex gap-2 mt-1 flex-wrap">
                                    <?php if ($a['formato']): ?>
                                    <span class="badge bg-light text-dark border" style="font-size:.7rem"><?= htmlspecialchars($a['formato']) ?></span>
                                    <?php endif; ?>
                                    <span class="text-muted" style="font-size:.75rem"><?= formatBytes((int)($a['tamanho_bytes'] ?? 0)) ?></span>
                                    <span class="text-muted" style="font-size:.75rem">ATA <?= $a['indice'] ?></span>
                                    <?php if ($a['download_ok']): ?>
                                        <?php if ($existeNoDisco): ?>
                                        <span class="badge bg-success" style="font-size:.7rem"><i class="bi bi-check me-1"></i>OK</span>
                                        <?php else: ?>
                                        <span class="badge bg-warning text-dark" style="font-size:.7rem" title="Removido do disco"><i class="bi bi-exclamation-triangle me-1"></i>Removido</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                    <span class="badge bg-danger" style="font-size:.7rem"><i class="bi bi-x me-1"></i>Falhou</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($existeNoDisco): ?>
                            <a href="<?= API_DOWNLOAD_URL ?>?endpoint=download_arquivo_id&id=<?= $a['id'] ?>"
                               class="btn btn-sm btn-outline-success flex-shrink-0" title="Baixar" target="_blank">
                                <i class="bi bi-download"></i>
                            </a>
                            <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary flex-shrink-0" disabled title="Arquivo não disponível">
                                <i class="bi bi-x-circle"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Coluna direita: logs -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center pt-3 pb-0 px-3">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-clock-history text-primary me-2"></i>Histórico de Logs
                </h6>
                <span class="badge bg-secondary"><?= count($logs) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($logs)): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-3"></i>
                    <p class="small mb-0 mt-1">Nenhum log registrado</p>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush" style="border-radius:0 0 12px 12px;overflow:hidden;">
                    <?php foreach (array_reverse($logs) as $log):
                        $corStatus = [
                            'INFO'        => 'primary',
                            'WARNING'     => 'warning',
                            'ERROR'       => 'danger',
                            'PENDENTE'    => 'warning',
                            'CONSULTANDO' => 'info',
                            'FINALIZADO'  => 'success',
                            'ERRO'        => 'danger',
                        ][$log['status']] ?? 'secondary';
                    ?>
                    <div class="list-group-item border-0 px-3 py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1 me-2">
                                <span class="badge bg-<?= $corStatus ?> me-2"><?= htmlspecialchars($log['status'] ?? '?') ?></span>
                                <span class="small"><?= htmlspecialchars($log['mensagem']) ?></span>
                            </div>
                            <small class="text-muted text-nowrap"><?= formatData($log['criado_em']) ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

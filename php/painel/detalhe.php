<?php
require_once 'config.php';
requerLogin();

$db = db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if(!$id){ header("Location: processos.php"); exit; }

$stmt = $db->prepare("SELECT * FROM processos WHERE id = ?");
$stmt->execute([$id]);
$processo = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$processo){ header("Location: processos.php"); exit; }

// Logs do processo
$logStmt = $db->prepare("
    SELECT mensagem, status, criado_em
    FROM processos_logs
    WHERE id_processo = ?
    ORDER BY criado_em DESC
");
$logStmt->execute([$id]);
$logs = $logStmt->fetchAll(PDO::FETCH_ASSOC);

$paginaAtual  = 'processos.php';
$tituloPagina = 'Detalhe: ' . $processo['numero_processo'];

include 'layout_header.php';
?>

<div class="mb-3">
    <a href="processos.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<div class="row g-3">
    <!-- Informações do processo -->
    <div class="col-lg-5">
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
                    <tr>
                        <th class="text-muted fw-normal">Cadastrado em</th>
                        <td><?= formatData($processo['criado_em']) ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Última consulta</th>
                        <td><?= formatData($processo['data_ultima_consulta']) ?></td>
                    </tr>
                    <?php if(!empty($processo['mensagem_erro'])): ?>
                    <tr>
                        <th class="text-muted fw-normal">Erro</th>
                        <td class="text-danger small"><?= htmlspecialchars($processo['mensagem_erro']) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- Arquivo / ATA -->
        <?php if(!empty($processo['caminho_arquivo'])): ?>
        <div class="card border-0 shadow-sm mt-3" style="border-radius:12px;">
            <div class="card-body p-3">
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-file-earmark-arrow-down text-success me-2"></i>Arquivo / ATA
                </h6>
                <p class="small text-muted mb-2">
                    <i class="bi bi-folder2 me-1"></i>
                    <span class="font-monospace"><?= htmlspecialchars($processo['caminho_arquivo']) ?></span>
                </p>
                <a href="../index.php?endpoint=download_arquivo&id=<?= $id ?>"
                   class="btn btn-success btn-sm"
                   target="_blank">
                    <i class="bi bi-download me-1"></i>Baixar arquivo
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="card border-0 shadow-sm mt-3 border-dashed" style="border-radius:12px;">
            <div class="card-body p-3 text-center text-muted">
                <i class="bi bi-file-earmark-x fs-3"></i>
                <p class="small mb-0 mt-1">Nenhum arquivo associado a este processo</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Logs -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center pt-3 pb-0 px-3">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-clock-history text-primary me-2"></i>Histórico de Logs
                </h6>
                <span class="badge bg-secondary"><?= count($logs) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if(empty($logs)): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-3"></i>
                    <p class="small mb-0 mt-1">Nenhum log registrado</p>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush" style="border-radius:0 0 12px 12px;overflow:hidden;">
                    <?php foreach($logs as $log):
                        $corStatus = [
                            'PENDENTE'    => 'warning',
                            'CONSULTANDO' => 'info',
                            'FINALIZADO'  => 'success',
                            'ERRO'        => 'danger',
                        ][$log['status']] ?? 'secondary';
                    ?>
                    <div class="list-group-item border-0 px-3 py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1 me-2">
                                <span class="badge bg-<?= $corStatus ?> me-2"><?= htmlspecialchars($log['status']) ?></span>
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

<?php include 'layout_footer.php'; ?>

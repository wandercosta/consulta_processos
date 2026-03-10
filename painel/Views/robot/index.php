<?php
/**
 * Variáveis injetadas pelo RobotController:
 * @var array  $config  — linha da tabela robot_config
 * @var bool   $vivo    — true se heartbeat < 30s
 */

$ativo    = (bool)(int)$config['ativo'];
$status   = $config['status']       ?? 'desconhecido';
$pid      = $config['pid']          ?? null;
$msg      = $config['mensagem']     ?? '—';
$ciclo    = $config['ultimo_ciclo'] ?? null;
$beat     = $config['atualizado_em'] ?? null;

// Usa segundos calculados pelo MySQL (TIMESTAMPDIFF) — sem divergência de fuso
$segundos = isset($config['segundos_desde_beat']) ? (int)$config['segundos_desde_beat'] : null;
$tempoHb  = $segundos !== null && $segundos >= 0
    ? ($segundos < 60 ? "{$segundos}s atrás" : gmdate('i\m s\s', $segundos) . ' atrás')
    : 'nunca';

// Mapa de status → badge
$badgeMap = [
    'parado'      => 'secondary',
    'aguardando'  => 'primary',
    'verificando' => 'info',
    'executando'  => 'warning',
    'erro'        => 'danger',
    'desconhecido'=> 'dark',
];
$badgeColor = $badgeMap[$status] ?? 'dark';
?>

<!-- ── Cabeçalho da página ──────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h5 class="mb-0 fw-bold"><i class="bi bi-robot text-primary me-2"></i>Controle do Robô</h5>
        <small class="text-muted">Daemon Python — automação headless de processos</small>
    </div>
    <!-- Badge daemon vivo/offline -->
    <?php if ($vivo): ?>
    <span class="badge bg-success fs-6 px-3 py-2">
        <i class="bi bi-circle-fill me-1" style="font-size:.55rem"></i> Daemon online
    </span>
    <?php else: ?>
    <span class="badge bg-secondary fs-6 px-3 py-2">
        <i class="bi bi-circle me-1" style="font-size:.55rem"></i> Daemon offline
    </span>
    <?php endif; ?>
</div>

<div class="row g-4">

    <!-- ── Coluna esquerda: controle ON/OFF ─────────────────────── -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px">
            <div class="card-body p-4 d-flex flex-column align-items-center text-center">

                <i class="bi bi-robot mb-3" style="font-size:3rem;color:<?= $ativo ? '#10b981' : '#94a3b8' ?>"></i>
                <h6 class="fw-bold mb-1">Automação Automática</h6>
                <p class="text-muted small mb-4">
                    Quando ativa, o daemon verifica a fila a cada ~10 s e processa os processos pendentes automaticamente.
                </p>

                <!-- Toggle switch grande -->
                <div class="mb-4">
                    <div class="robot-toggle <?= $ativo ? 'on' : 'off' ?>" id="toggleVisual">
                        <span class="toggle-knob"></span>
                        <span class="toggle-label"><?= $ativo ? 'LIGADO' : 'DESLIGADO' ?></span>
                    </div>
                </div>

                <!-- Botão de ação -->
                <form method="POST" action="<?= PAINEL_URL ?>?page=robot&action=toggle" id="formToggle">
                    <input type="hidden" name="ativo" value="<?= $ativo ? '0' : '1' ?>" id="inputAtivo">
                    <button type="submit"
                            class="btn btn-lg px-4 <?= $ativo ? 'btn-danger' : 'btn-success' ?>">
                        <?php if ($ativo): ?>
                            <i class="bi bi-stop-circle me-2"></i>Desativar Robô
                        <?php else: ?>
                            <i class="bi bi-play-circle me-2"></i>Ativar Robô
                        <?php endif; ?>
                    </button>
                </form>

                <?php if (!$vivo && $ativo): ?>
                <div class="alert alert-warning mt-3 mb-0 small text-start w-100">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>Daemon offline!</strong> O robô está ativado no painel mas nenhum heartbeat foi recebido.
                    Execute <code>python daemon.py</code> na máquina local.
                </div>
                <?php endif; ?>

                <?php if (!$ativo && !$vivo): ?>
                <p class="text-muted small mt-3 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Para usar, execute <code>python daemon.py</code> e ative aqui.
                </p>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- ── Coluna direita: status e heartbeat ───────────────────── -->
    <div class="col-lg-8">

        <!-- Cards de métricas -->
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="stat-card" style="background:linear-gradient(135deg,#3b82f6,#2563eb)">
                    <i class="bi bi-activity stat-icon"></i>
                    <div>
                        <div class="stat-value">
                            <span class="badge bg-<?= $badgeColor ?> fs-6">
                                <?= htmlspecialchars(ucfirst($status)) ?>
                            </span>
                        </div>
                        <div class="stat-label">Status atual</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="stat-card" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed)">
                    <i class="bi bi-heart-pulse stat-icon"></i>
                    <div>
                        <div class="stat-value" style="font-size:1.1rem"><?= htmlspecialchars($tempoHb) ?></div>
                        <div class="stat-label">Último heartbeat</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="stat-card" style="background:linear-gradient(135deg,#10b981,#059669)">
                    <i class="bi bi-cpu stat-icon"></i>
                    <div>
                        <div class="stat-value" style="font-size:1.1rem"><?= $pid ? "PID {$pid}" : '—' ?></div>
                        <div class="stat-label">Processo Python</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card de detalhes -->
        <div class="card border-0 shadow-sm" style="border-radius:14px">
            <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between pt-3 pb-0 px-3">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-info-circle text-primary me-1"></i> Detalhes do Daemon
                </h6>
                <span class="badge bg-light text-muted border" id="refreshBadge">
                    <i class="bi bi-arrow-clockwise me-1"></i>Atualiza em <span id="countdown">10</span>s
                </span>
            </div>
            <div class="card-body px-3 pb-3 pt-2">
                <table class="table table-sm table-borderless mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted small fw-semibold" style="width:160px">Ativo (BD)</td>
                            <td>
                                <?php if ($ativo): ?>
                                    <span class="badge bg-success">Sim</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Não</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted small fw-semibold">Daemon online</td>
                            <td>
                                <?php if ($vivo): ?>
                                    <span class="badge bg-success">Sim</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Não</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted small fw-semibold">Último ciclo</td>
                            <td class="small"><?= $ciclo ? htmlspecialchars($ciclo) : '—' ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small fw-semibold">Atualizado em</td>
                            <td class="small"><?= $beat ? htmlspecialchars($beat) : '—' ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small fw-semibold">Mensagem</td>
                            <td class="small font-monospace" id="msgDaemon">
                                <?= htmlspecialchars($msg ?: '—') ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Instruções de uso -->
        <div class="card border-0 bg-light mt-3" style="border-radius:14px">
            <div class="card-body px-3 py-3">
                <h6 class="fw-bold mb-2"><i class="bi bi-terminal me-1"></i>Como usar</h6>
                <ol class="small mb-0 ps-3">
                    <li>Execute <code>python daemon.py --headless</code> na máquina local</li>
                    <li>O daemon ficará em standby verificando o painel a cada 10 s</li>
                    <li>Clique em <strong>Ativar Robô</strong> aqui no painel</li>
                    <li>O daemon detecta a ativação e começa a processar a fila</li>
                    <li>Clique em <strong>Desativar Robô</strong> para pausar (o ciclo atual termina antes de parar)</li>
                </ol>
            </div>
        </div>

    </div><!-- /col-lg-8 -->
</div><!-- /row -->

<!-- ── Estilos do toggle ──────────────────────────────────────────── -->
<style>
.robot-toggle {
    position: relative;
    width: 160px;
    height: 60px;
    border-radius: 30px;
    cursor: pointer;
    display: flex;
    align-items: center;
    padding: 0 12px;
    transition: background .3s;
    user-select: none;
}
.robot-toggle.on  { background: #10b981; justify-content: flex-end; }
.robot-toggle.off { background: #94a3b8; justify-content: flex-start; }
.toggle-knob {
    position: absolute;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 2px 6px rgba(0,0,0,.25);
    transition: left .3s;
    top: 6px;
}
.robot-toggle.on  .toggle-knob { left: calc(100% - 54px); }
.robot-toggle.off .toggle-knob { left: 6px; }
.toggle-label {
    font-size: .8rem;
    font-weight: 700;
    color: #fff;
    letter-spacing: .05em;
}
</style>

<!-- ── Auto-refresh: recarrega a página completa a cada 10s ──────── -->
<script>
(function () {
    const INTERVAL = 10; // segundos
    let counter    = INTERVAL;
    const countdown = document.getElementById('countdown');

    // Contador regressivo visível
    const tick = setInterval(() => {
        counter--;
        if (countdown) countdown.textContent = counter;
        if (counter <= 0) {
            clearInterval(tick);
            // Recarrega a página inteira para atualizar todos os elementos:
            // badge daemon online/offline, alerta, cards de status, PID, etc.
            location.reload();
        }
    }, 1000);
})();
</script>

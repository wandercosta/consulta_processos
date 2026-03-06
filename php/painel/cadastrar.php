<?php
require_once 'config.php';
requerLogin();

$paginaAtual  = 'cadastrar.php';
$tituloPagina = 'Cadastrar Processo';

$db    = db();
$erro  = '';
$sucesso = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $numero = trim($_POST['numero_processo'] ?? '');

    if($numero === ''){
        $erro = 'O número do processo é obrigatório.';
    } else {
        // Verifica duplicata
        $check = $db->prepare("SELECT id FROM processos WHERE numero_processo = ?");
        $check->execute([$numero]);
        if($check->fetch()){
            $erro = 'Este processo já está cadastrado.';
        } else {
            $stmt = $db->prepare("INSERT INTO processos (numero_processo, status_consulta, criado_em) VALUES (?, 'PENDENTE', NOW())");
            $stmt->execute([$numero]);
            $novoId = $db->lastInsertId();
            $sucesso = "Processo <strong>" . htmlspecialchars($numero) . "</strong> cadastrado com sucesso! ID: {$novoId}";
        }
    }
}

include 'layout_header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">
                    <i class="bi bi-plus-circle text-primary me-2"></i>Novo Processo
                </h5>

                <?php if($erro): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($erro) ?>
                </div>
                <?php endif; ?>

                <?php if($sucesso): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i><?= $sucesso ?>
                    <div class="mt-2">
                        <a href="cadastrar.php" class="btn btn-sm btn-success me-2">Cadastrar outro</a>
                        <a href="processos.php" class="btn btn-sm btn-outline-secondary">Ver todos</a>
                    </div>
                </div>
                <?php else: ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Número do Processo</label>
                        <input type="text" name="numero_processo"
                               class="form-control font-monospace"
                               placeholder="Ex: 0001234-56.2024.8.26.0001"
                               value="<?= htmlspecialchars($_POST['numero_processo'] ?? '') ?>"
                               autofocus required>
                        <div class="form-text">Informe o número único do processo judicial/administrativo.</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Cadastrar
                        </button>
                        <a href="processos.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Dica: cadastro em lote -->
        <div class="card border-0 shadow-sm mt-3" style="border-radius:12px;">
            <div class="card-body p-3">
                <h6 class="fw-bold mb-2 text-muted">
                    <i class="bi bi-list-ul me-2"></i>Cadastro em lote via API
                </h6>
                <p class="small text-muted mb-2">Você pode cadastrar múltiplos processos via API usando o endpoint:</p>
                <code class="d-block bg-light rounded p-2 small">
                    POST /processos_api/?endpoint=cadastrar_processo<br>
                    Authorization: Bearer CLAUDE_AUTOMACAO_123<br>
                    {"numero_processo": "..."}
                </code>
            </div>
        </div>
    </div>
</div>

<?php include 'layout_footer.php'; ?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — Painel Processos</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
body { background: #f0f2f5; }
.login-card { max-width: 360px; margin: 100px auto; }
</style>
</head>
<body>
<div class="login-card card shadow-sm p-4">
    <div class="text-center mb-4">
        <i class="bi bi-folder2-open fs-1 text-primary"></i>
        <h5 class="mt-2 fw-bold">Painel de Processos</h5>
    </div>
    <?php if (!empty($erro)): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Senha</label>
            <input type="password" name="senha" class="form-control" autofocus required>
        </div>
        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-box-arrow-in-right"></i> Entrar
        </button>
    </form>
</div>
</body>
</html>

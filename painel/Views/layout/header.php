<?php
$paginaAtual  = $paginaAtual  ?? 'dashboard';
$tituloPagina = $tituloPagina ?? 'Painel';

$nav = [
    'dashboard' => ['icon' => 'bi-speedometer2',           'label' => 'Dashboard'],
    'processos' => ['icon' => 'bi-journal-text',            'label' => 'Processos'],
    'cadastrar' => ['icon' => 'bi-plus-circle',             'label' => 'Cadastrar'],
    'arquivos'  => ['icon' => 'bi-file-earmark-arrow-down', 'label' => 'Arquivos / ATAs'],
    'robot'     => ['icon' => 'bi-robot',                   'label' => 'Robô'],
    'webhook'   => ['icon' => 'bi-send',                    'label' => 'Webhooks'],
    'docs'      => ['icon' => 'bi-book',                    'label' => 'Documentação'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($tituloPagina) ?> — Painel Processos</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= PAINEL_URL_BASE ?>assets/style.css">
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="brand">
        <i class="bi bi-folder2-open me-2"></i>Processos
    </div>
    <nav class="mt-2">
        <?php foreach ($nav as $page => $item): ?>
        <a href="<?= PAINEL_URL ?>?page=<?= $page ?>"
           class="nav-link <?= $paginaAtual === $page ? 'active' : '' ?>">
            <i class="bi <?= $item['icon'] ?>"></i> <?= $item['label'] ?>
        </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
        <a href="<?= PAINEL_URL ?>?page=logout" class="text-danger text-decoration-none small">
            <i class="bi bi-box-arrow-right"></i> Sair
        </a>
    </div>
</aside>

<!-- Conteúdo principal -->
<div class="main-content">
    <div class="topbar">
        <span class="fw-semibold text-dark"><?= htmlspecialchars($tituloPagina) ?></span>
        <span class="text-muted small">
            <i class="bi bi-calendar2"></i> <?= date('d/m/Y') ?>
        </span>
    </div>
    <div class="page-body">

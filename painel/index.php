<?php

define('PAINEL_ROOT',     __DIR__);
define('PAINEL_URL',      '/processos_api/painel/index.php');
define('PAINEL_URL_BASE', '/processos_api/painel/');
define('API_DOWNLOAD_URL','/processos_api/api/index.php');

require_once PAINEL_ROOT . '/config/config.php';

$page = $_GET['page'] ?? 'dashboard';

// ── Auth: rotas públicas ──────────────────────────────────────────────────────
if ($page === 'login' || $page === 'logout') {
    require_once PAINEL_ROOT . '/Controllers/AuthController.php';
    $ctrl = new AuthController();
    $ctrl->$page();
    exit;
}

// ── Todas as demais rotas exigem login ────────────────────────────────────────
requerLogin();

// ── Roteamento ────────────────────────────────────────────────────────────────
require_once PAINEL_ROOT . '/Controllers/BaseController.php';
require_once PAINEL_ROOT . '/Models/ProcessoModel.php';
require_once PAINEL_ROOT . '/Models/ArquivoModel.php';

switch ($page) {

    case 'dashboard':
        require_once PAINEL_ROOT . '/Controllers/DashboardController.php';
        (new DashboardController(new ProcessoModel(db())))->index();
        break;

    case 'processos':
        require_once PAINEL_ROOT . '/Controllers/ProcessoController.php';
        (new ProcessoController(new ProcessoModel(db())))->index();
        break;

    case 'detalhe':
        require_once PAINEL_ROOT . '/Controllers/ProcessoController.php';
        (new ProcessoController(new ProcessoModel(db()), new ArquivoModel(db())))->detalhe();
        break;

    case 'cadastrar':
        require_once PAINEL_ROOT . '/Controllers/ProcessoController.php';
        (new ProcessoController(new ProcessoModel(db())))->cadastrar();
        break;

    case 'arquivos':
        require_once PAINEL_ROOT . '/Controllers/ArquivoController.php';
        (new ArquivoController(new ArquivoModel(db())))->index();
        break;

    default:
        header("Location: " . PAINEL_URL . "?page=dashboard");
        exit;
}

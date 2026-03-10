<?php

define('PAINEL_ROOT', __DIR__);

// ── Variáveis de ambiente (.env) ──────────────────────────────────────────────
// Carregado antes dos defines de URL para que APP_BASE_PATH seja resolvido
require_once dirname(__DIR__) . '/api/config/Env.php';
Env::load(dirname(__DIR__) . '/.env');

// Local  → APP_BASE_PATH=/processos_api
// Produção → APP_BASE_PATH=   (vazio, conteúdo na raiz do servidor)
$_basePath = rtrim(Env::get('APP_BASE_PATH', ''), '/');

define('PAINEL_URL',      $_basePath . '/painel/index.php');
define('PAINEL_URL_BASE', $_basePath . '/painel/');
define('API_DOWNLOAD_URL',$_basePath . '/api/index.php');

unset($_basePath);

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

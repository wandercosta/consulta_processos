<?php

header("Content-Type: application/json");

// ── Variáveis de ambiente (.env) ──────────────────────────────────────────────
require_once __DIR__ . '/config/Env.php';
Env::load(dirname(__DIR__) . '/.env');

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/Auth.php';

require_once __DIR__ . '/Domain/Processo/ProcessoRepositoryInterface.php';
require_once __DIR__ . '/Domain/Arquivo/ArquivoRepositoryInterface.php';
require_once __DIR__ . '/Infrastructure/ProcessoRepositoryPDO.php';
require_once __DIR__ . '/Infrastructure/ArquivoRepositoryPDO.php';
require_once __DIR__ . '/Http/Controllers/ProcessoController.php';
require_once __DIR__ . '/Http/Controllers/ArquivoController.php';

validarToken();

$db = (new Database())->connect();

$processoCtrl = new ProcessoController(new ProcessoRepositoryPDO($db));
$arquivoCtrl  = new ArquivoController(new ArquivoRepositoryPDO($db));

$endpoint = $_GET['endpoint'] ?? '';

switch ($endpoint) {

    case 'processos_pendentes':
        $processoCtrl->pendentes();
        break;

    case 'registrar_consulta':
        $processoCtrl->registrarConsulta();
        break;

    case 'registrar_ata':
        $processoCtrl->registrarAta();
        break;

    case 'registrar_sem_ata':
        $processoCtrl->registrarSemAta();
        break;

    case 'registrar_erro':
        $processoCtrl->registrarErro();
        break;

    case 'status_processo':
        $processoCtrl->status();
        break;

    case 'logs':
        $processoCtrl->logs();
        break;

    case 'cadastrar_processo':
        $processoCtrl->cadastrar();
        break;

    case 'listar_processos':
        $processoCtrl->listar();
        break;

    case 'registrar_arquivo':
        $arquivoCtrl->registrar();
        break;

    case 'download_arquivo':
        $arquivoCtrl->downloadByProcesso();
        break;

    case 'download_arquivo_id':
        $arquivoCtrl->downloadById();
        break;

    case 'upload_arquivo':
        $arquivoCtrl->uploadArquivo();
        break;

    default:
        http_response_code(404);
        echo json_encode(["erro" => "Endpoint não encontrado"]);
}

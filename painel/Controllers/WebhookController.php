<?php

class WebhookController extends BaseController
{
    private WebhookModel $model;

    public function __construct(WebhookModel $model)
    {
        $this->model = $model;
    }

    public function index(): void
    {
        $pagina    = max(1, (int)($_GET['pagina'] ?? 1));
        $resultado = $this->model->listarLogs($pagina, 30);
        $logs      = $resultado['dados'];
        $total     = $resultado['total'];
        $paginas   = $resultado['paginas'];
        $config    = $this->model->getConfig();

        $this->render('webhook/index', compact('logs', 'total', 'paginas', 'pagina', 'config')
            + ['paginaAtual' => 'webhook', 'tituloPagina' => 'Webhooks']);
    }

    public function salvarConfig(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('webhook');
        }

        $url    = trim($_POST['url']    ?? '');
        $ativo  = isset($_POST['ativo']) ? 1 : 0;
        $secret = trim($_POST['secret'] ?? '');

        $this->model->salvarConfig($url, $ativo, $secret);
        $this->redirect('webhook');
    }

    public function reenviar(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            $this->redirect('webhook');
        }

        // Usa o WebhookService para reenvio (precisa do PDO da API ou do painel)
        require_once PAINEL_ROOT . '/../api/Infrastructure/WebhookService.php';

        $basePath = rtrim(Env::get('APP_BASE_PATH', ''), '/');
        $apiBase  = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://'
                  . $_SERVER['HTTP_HOST'] . $basePath . '/api';

        $log = $this->model->getLog($id);
        if (!$log) {
            $this->redirect('webhook');
        }

        $config = $this->model->getConfig();
        if (empty($config['url'])) {
            $this->redirect('webhook');
        }

        $service = new WebhookService(db(), $apiBase);
        $payload = json_decode($log['payload'], true) ?? [];
        $service->enviar($config, (int)$log['id_processo'], $payload);

        $this->redirect('webhook');
    }
}

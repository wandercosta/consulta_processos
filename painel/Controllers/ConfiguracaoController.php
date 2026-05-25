<?php

class ConfiguracaoController extends BaseController
{
    private ConfiguracaoModel $model;

    // Extensões disponíveis para seleção
    private const EXTENSOES_DISPONIVEIS = ['pdf', 'html', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'xml'];

    public function __construct(ConfiguracaoModel $model)
    {
        $this->model = $model;
    }

    public function index(): void
    {
        $config   = $this->model->getAll();
        $sucesso  = '';
        $erro     = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $maxTentativas = (int)($_POST['max_tentativas'] ?? 10);
            $extensoes     = $_POST['extensoes'] ?? [];

            if ($maxTentativas < 1 || $maxTentativas > 100) {
                $erro = 'Número de tentativas deve estar entre 1 e 100.';
            } elseif (empty($extensoes)) {
                $erro = 'Selecione ao menos uma extensão de arquivo.';
            } else {
                $exts = array_filter(array_map('strtolower', $extensoes));
                $this->model->set('max_tentativas',    (string)$maxTentativas);
                $this->model->set('extensoes_aceitas', implode(',', $exts));
                $config   = $this->model->getAll();
                $sucesso  = 'Configurações salvas com sucesso.';
            }
        }

        $extsAtuais = array_filter(array_map('trim', explode(',', $config['extensoes_aceitas'] ?? 'pdf,html')));

        $this->render('configuracoes/index', compact('config', 'sucesso', 'erro', 'extsAtuais') + [
            'extensoesDisponiveis' => self::EXTENSOES_DISPONIVEIS,
            'paginaAtual'          => 'configuracoes',
            'tituloPagina'         => 'Configurações',
        ]);
    }
}

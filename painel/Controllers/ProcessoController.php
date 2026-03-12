<?php

class ProcessoController extends BaseController
{
    private ProcessoModel $model;
    private ?ArquivoModel $arquivoModel;

    public function __construct(ProcessoModel $model, ?ArquivoModel $arquivoModel = null)
    {
        $this->model        = $model;
        $this->arquivoModel = $arquivoModel;
    }

    public function index(): void
    {
        $filtros = [
            'status'     => $_GET['status']     ?? '',
            'search'     => $_GET['search']     ?? '',
            'possui_ata' => $_GET['possui_ata'] ?? '',
            'data_de'    => $_GET['data_de']    ?? '',
            'data_ate'   => $_GET['data_ate']   ?? '',
        ];
        $pagina  = max(1, (int)($_GET['pagina'] ?? 1));
        $limite  = 20;

        $resultado  = $this->model->listar($filtros, $pagina, $limite);
        $processos  = $resultado['dados'];
        $total      = $resultado['total'];
        $paginas    = $resultado['paginas'];
        $queryBase  = http_build_query(array_filter($filtros));

        $this->render('processos/index', compact(
            'processos', 'total', 'paginas', 'pagina', 'limite',
            'filtros', 'queryBase',
        ) + ['paginaAtual' => 'processos', 'tituloPagina' => 'Processos']);
    }

    public function detalhe(): void
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) {
            $this->redirect('processos');
        }

        $processo = $this->model->findById($id);
        if (!$processo) {
            $this->redirect('processos');
        }

        $logs     = $this->model->getLogs($id);
        $arquivos = $this->arquivoModel ? $this->arquivoModel->findByProcesso($id) : [];

        $this->render('processos/detalhe', compact('processo', 'logs', 'arquivos')
            + ['paginaAtual' => 'processos', 'tituloPagina' => 'Detalhe: ' . $processo['numero_processo']]);
    }

    public function cadastrar(): void
    {
        $erro    = '';
        $sucesso = '';

        // Tribunais disponíveis — adicione aqui quando novos conectores forem implementados
        $tribunais = ['TJMG' => 'TJMG — Tribunal de Justiça de Minas Gerais'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $numero   = trim($_POST['numero_processo'] ?? '');
            $tribunal = strtoupper(trim($_POST['tribunal'] ?? ''));
            $dataAto  = trim($_POST['data_ato'] ?? '');

            if ($numero === '') {
                $erro = 'O número do processo é obrigatório.';
            } elseif (!array_key_exists($tribunal, $tribunais)) {
                $erro = 'Tribunal inválido.';
            } elseif ($dataAto !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataAto)) {
                $erro = 'Data do ato inválida.';
            } elseif ($this->model->existeNumero($numero)) {
                $erro = 'Este processo já está cadastrado.';
            } else {
                $novoId  = $this->model->criar($numero, $tribunal, $dataAto ?: null);
                $sucesso = "Processo <strong>" . htmlspecialchars($numero) . "</strong> cadastrado no <strong>{$tribunal}</strong> com sucesso! ID: {$novoId}";
            }
        }

        $this->render('processos/cadastrar', compact('erro', 'sucesso', 'tribunais')
            + ['paginaAtual' => 'cadastrar', 'tituloPagina' => 'Cadastrar Processo']);
    }
}

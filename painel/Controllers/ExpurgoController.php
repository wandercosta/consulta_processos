<?php

class ExpurgoController extends BaseController
{
    private ExpurgoModel $model;

    public function __construct(ExpurgoModel $model)
    {
        $this->model = $model;
    }

    public function index(): void
    {
        $filtros = [
            'search'   => $_GET['search']   ?? '',
            'data_de'  => $_GET['data_de']  ?? '',
            'data_ate' => $_GET['data_ate'] ?? '',
        ];
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $limite = 25;

        $stats     = $this->model->getStats();
        $resultado = $this->model->listar($filtros, $pagina, $limite);
        $registros = $resultado['dados'];
        $total     = $resultado['total'];
        $paginas   = $resultado['paginas'];
        $queryBase = http_build_query(array_filter($filtros));

        $this->render('expurgo/index', compact(
            'stats', 'registros', 'total', 'paginas', 'pagina', 'filtros', 'queryBase',
        ) + ['paginaAtual' => 'expurgo', 'tituloPagina' => 'Expurgo de Arquivos']);
    }

    // Executa o expurgo manualmente via painel (POST)
    public function executar(): void
    {
        $dias      = max(1, (int)($_POST['dias'] ?? 10));
        $elegiveis = $this->model->getElegiveis($dias);
        $removidos = 0;
        $erros     = 0;
        $libBytes  = 0;

        foreach ($elegiveis as $arq) {
            $caminho = $arq['caminho_arquivo'];

            // Tenta apagar o arquivo físico
            $apagou = true;
            if (file_exists($caminho)) {
                $apagou = @unlink($caminho);
            }

            if ($apagou) {
                $this->model->registrarExpurgo($arq, "expurgo_manual_{$dias}d");
                $this->model->removerDaTabela((int)$arq['id']);
                $libBytes += (int)$arq['tamanho_bytes'];
                $removidos++;
            } else {
                $erros++;
            }
        }

        $_SESSION['expurgo_msg'] = [
            'tipo'      => $erros === 0 ? 'success' : 'warning',
            'texto'     => "Expurgo concluído: {$removidos} arquivo(s) removido(s)" .
                           ($erros ? ", {$erros} erro(s)" : '') .
                           ". Espaço liberado: " . $this->formatBytes($libBytes) . ".",
        ];

        header("Location: " . PAINEL_URL . "?page=expurgo");
        exit;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 2)    . ' MB';
        if ($bytes >= 1024)       return round($bytes / 1024, 2)       . ' KB';
        return $bytes . ' B';
    }
}

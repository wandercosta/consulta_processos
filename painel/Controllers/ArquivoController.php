<?php

class ArquivoController extends BaseController
{
    public function __construct(private ArquivoModel $model) {}

    public function index(): void
    {
        $filtros = [
            'search'   => $_GET['search']   ?? '',
            'formato'  => $_GET['formato']  ?? '',
            'data_de'  => $_GET['data_de']  ?? '',
            'data_ate' => $_GET['data_ate'] ?? '',
        ];
        $pagina  = max(1, (int)($_GET['pagina'] ?? 1));
        $limite  = 25;

        $stats      = $this->model->getStats();
        $resultado  = $this->model->listar($filtros, $pagina, $limite);
        $arquivos   = $resultado['dados'];
        $total      = $resultado['total'];
        $paginas    = $resultado['paginas'];
        $queryBase  = http_build_query(array_filter($filtros));

        $this->render('arquivos/index', compact(
            'stats', 'arquivos', 'total', 'paginas', 'pagina', 'filtros', 'queryBase',
        ) + ['paginaAtual' => 'arquivos', 'tituloPagina' => 'Arquivos / ATAs']);
    }
}

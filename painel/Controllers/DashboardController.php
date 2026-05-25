<?php

class DashboardController extends BaseController
{
    private ProcessoModel $processoModel;

    public function __construct(ProcessoModel $processoModel)
    {
        $this->processoModel = $processoModel;
    }

    public function index(): void
    {
        $totais      = $this->processoModel->getTotaisPorStatus();
        $totalGeral  = array_sum($totais);
        $comAta      = ($totais['FINALIZADO COM ATA'] ?? 0) + ($totais['FINALIZADO'] ?? 0);
        $semAta      = $totais['FINALIZADO SEM ATA'] ?? 0;
        $reprocessando = $this->processoModel->countReprocessando();
        $proximos    = $this->processoModel->getProximosDaFila();
        $processados = $this->processoModel->getUltimosProcessados();
        $ultimos     = $this->processoModel->getUltimosCadastrados();
        $logs           = $this->processoModel->getUltimosLogs();
        $semAtaFila     = $this->processoModel->getSemAtaAguardando();
        $consultando    = $this->processoModel->getConsultando();
        $maxTentativas  = $this->processoModel->maxTentativas();

        $this->render('dashboard/index', compact(
            'totais', 'totalGeral', 'comAta', 'semAta', 'reprocessando',
            'proximos', 'processados', 'ultimos', 'logs', 'semAtaFila', 'consultando',
            'maxTentativas',
        ) + ['paginaAtual' => 'dashboard', 'tituloPagina' => 'Dashboard']);
    }
}

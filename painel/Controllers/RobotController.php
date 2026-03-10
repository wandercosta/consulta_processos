<?php

class RobotController extends BaseController
{
    private RobotModel $model;

    public function __construct(RobotModel $model)
    {
        $this->model = $model;
    }

    /** GET ?page=robot — exibe o painel de controle do daemon */
    public function index(): void
    {
        $config = $this->model->getConfig();
        $vivo   = $this->model->isDaemonVivo();

        $this->render('robot/index', compact('config', 'vivo'));
    }

    /** POST ?page=robot&action=toggle — liga ou desliga o daemon */
    public function toggle(): void
    {
        $ativo = (bool)(int)($_POST['ativo'] ?? 0);
        $this->model->setAtivo($ativo);
        header("Location: " . PAINEL_URL . "?page=robot");
        exit;
    }
}

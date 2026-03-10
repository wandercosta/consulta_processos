<?php

class RobotController
{
    private RobotRepositoryInterface $repo;

    public function __construct(RobotRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    /** GET /robot_status — retorna configuração atual do daemon */
    public function status(): void
    {
        echo json_encode($this->repo->getConfig());
    }

    /** POST /robot_ativar — liga o daemon */
    public function ativar(): void
    {
        $this->repo->setAtivo(true);
        echo json_encode(["status" => "ativado"]);
    }

    /** POST /robot_desativar — desliga o daemon (aguarda ciclo atual terminar) */
    public function desativar(): void
    {
        $this->repo->setAtivo(false);
        echo json_encode(["status" => "desativado"]);
    }

    /**
     * POST /robot_heartbeat — chamado pelo daemon a cada ciclo.
     * Body JSON: { "status": string, "pid": int, "mensagem": string }
     */
    public function heartbeat(): void
    {
        $data     = json_decode(file_get_contents("php://input"), true) ?? [];
        $status   = trim($data['status']   ?? 'aguardando');
        $pid      = isset($data['pid'])    ? (int) $data['pid'] : null;
        $mensagem = isset($data['mensagem']) ? substr(trim($data['mensagem']), 0, 255) : null;

        $this->repo->updateHeartbeat($status, $pid, $mensagem);
        echo json_encode(["status" => "ok"]);
    }
}

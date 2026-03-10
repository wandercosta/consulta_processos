<?php

class RobotRepositoryPDO implements RobotRepositoryInterface
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getConfig(): array
    {
        $stmt = $this->db->query("SELECT * FROM robot_config WHERE id = 1");
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: [
            'id'           => 1,
            'ativo'        => 0,
            'status'       => 'desconhecido',
            'pid'          => null,
            'ultimo_ciclo' => null,
            'mensagem'     => 'Tabela robot_config não encontrada — execute painel/migrar_robot.php',
            'atualizado_em'=> null,
        ];
    }

    public function setAtivo(bool $ativo): void
    {
        $this->db->prepare("
            UPDATE robot_config SET ativo = ?, atualizado_em = NOW() WHERE id = 1
        ")->execute([(int) $ativo]);
    }

    public function updateHeartbeat(string $status, ?int $pid, ?string $mensagem): void
    {
        $this->db->prepare("
            UPDATE robot_config
            SET status = ?, pid = ?, mensagem = ?, ultimo_ciclo = NOW(), atualizado_em = NOW()
            WHERE id = 1
        ")->execute([$status, $pid, $mensagem]);
    }
}

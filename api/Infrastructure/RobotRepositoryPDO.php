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
        // UNIX_TIMESTAMP e TIMESTAMPDIFF calculados dentro do MySQL para evitar
        // problemas de fuso horário entre PHP (strtotime) e MySQL (NOW()).
        $stmt = $this->db->query("
            SELECT *,
                   UNIX_TIMESTAMP(atualizado_em)                       AS atualizado_ts,
                   TIMESTAMPDIFF(SECOND, atualizado_em, NOW())         AS segundos_desde_beat
            FROM robot_config WHERE id = 1
        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: [
            'id'                  => 1,
            'ativo'               => 0,
            'status'              => 'desconhecido',
            'pid'                 => null,
            'ultimo_ciclo'        => null,
            'mensagem'            => 'Tabela robot_config não encontrada — execute painel/migrar_robot.php',
            'atualizado_em'       => null,
            'atualizado_ts'       => null,
            'segundos_desde_beat' => null,
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

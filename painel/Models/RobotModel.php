<?php

class RobotModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Retorna a configuração atual do daemon */
    public function getConfig(): array
    {
        try {
            // UNIX_TIMESTAMP e TIMESTAMPDIFF calculados no MySQL para evitar
            // divergências de fuso horário entre PHP (strtotime) e MySQL (NOW()).
            $stmt = $this->db->query("
                SELECT *,
                       UNIX_TIMESTAMP(atualizado_em)                       AS atualizado_ts,
                       TIMESTAMPDIFF(SECOND, atualizado_em, NOW())         AS segundos_desde_beat
                FROM robot_config WHERE id = 1
            ");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: $this->_defaultConfig('Linha não encontrada — execute migrar_robot.php');
        } catch (Exception $e) {
            return $this->_defaultConfig('Tabela robot_config não existe — execute migrar_robot.php');
        }
    }

    /** Liga ou desliga o daemon */
    public function setAtivo(bool $ativo): void
    {
        $this->db->prepare("
            UPDATE robot_config SET ativo = ?, atualizado_em = NOW() WHERE id = 1
        ")->execute([(int) $ativo]);
    }

    /**
     * Retorna true se o daemon está vivo com base no último heartbeat.
     *
     * Usa segundos_desde_beat calculado pelo MySQL (TIMESTAMPDIFF) para
     * evitar divergências de fuso horário entre PHP e MySQL.
     *
     * Limites:
     *  - "executando" → até 5 min (scraping + download pode demorar)
     *  - demais status → até 60s (daemon heartbeat a cada ~10s quando idle)
     */
    public function isDaemonVivo(): bool
    {
        $config   = $this->getConfig();
        $segundos = isset($config['segundos_desde_beat']) ? (int) $config['segundos_desde_beat'] : null;

        if ($segundos === null) {
            return false;
        }

        $limite = ($config['status'] === 'executando') ? 300 : 60;
        return $segundos >= 0 && $segundos < $limite;
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function _defaultConfig(string $mensagem): array
    {
        return [
            'id'            => 1,
            'ativo'         => 0,
            'status'        => 'desconhecido',
            'pid'           => null,
            'ultimo_ciclo'  => null,
            'mensagem'      => $mensagem,
            'atualizado_em' => null,
        ];
    }
}

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
            $stmt = $this->db->query("SELECT * FROM robot_config WHERE id = 1");
            $row  = $stmt->fetch(PDO::FETCH_ASSOC);
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
     * O limite varia conforme o status:
     *  - "executando" → até 5 min (scraping + download pode demorar)
     *  - demais status → até 60s (daemon envia heartbeat a cada ~10s quando idle)
     */
    public function isDaemonVivo(): bool
    {
        $config = $this->getConfig();
        if (empty($config['atualizado_em'])) {
            return false;
        }
        $segundos = time() - strtotime($config['atualizado_em']);
        $limite   = ($config['status'] === 'executando') ? 300 : 60;
        return $segundos < $limite;
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

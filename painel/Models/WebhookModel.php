<?php

class WebhookModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ── Config ─────────────────────────────────────────────────────────────────

    public function getConfig(): array
    {
        $row = $this->db->query("SELECT * FROM webhook_config ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        return $row ?: ['id' => null, 'url' => '', 'ativo' => 0, 'secret' => ''];
    }

    public function salvarConfig(string $url, int $ativo, string $secret): void
    {
        $config = $this->getConfig();
        if ($config['id']) {
            $this->db->prepare("
                UPDATE webhook_config SET url = ?, ativo = ?, secret = ? WHERE id = ?
            ")->execute([$url, $ativo, $secret ?: null, $config['id']]);
        } else {
            $this->db->prepare("
                INSERT INTO webhook_config (url, ativo, secret) VALUES (?, ?, ?)
            ")->execute([$url, $ativo, $secret ?: null]);
        }
    }

    // ── Logs ───────────────────────────────────────────────────────────────────

    public function listarLogs(int $pagina = 1, int $limite = 30): array
    {
        $offset = ($pagina - 1) * $limite;
        $total  = (int)$this->db->query("SELECT COUNT(*) FROM webhook_logs")->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT wl.*, p.numero_processo, p.cod_api
            FROM webhook_logs wl
            JOIN processos p ON p.id = wl.id_processo
            ORDER BY wl.enviado_em DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $limite, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'dados'   => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total'   => $total,
            'paginas' => (int)ceil($total / $limite),
        ];
    }

    public function getLog(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM webhook_logs WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

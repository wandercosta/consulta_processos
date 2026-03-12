<?php

/**
 * WebhookService — dispara notificações HTTP para URL configurada na tabela webhook_config
 * e registra cada tentativa em webhook_logs.
 */
class WebhookService
{
    private PDO    $db;
    private string $apiBase; // URL pública base da API (para montar links de arquivo)

    public function __construct(PDO $db, string $apiBase)
    {
        $this->db      = $db;
        $this->apiBase = rtrim($apiBase, '/');
    }

    /**
     * Dispara o webhook para o processo informado, se houver URL configurada e ativa.
     */
    public function disparar(int $idProcesso): void
    {
        $config = $this->getConfig();
        if (!$config || !$config['ativo'] || empty($config['url'])) {
            return;
        }

        $processo = $this->getProcesso($idProcesso);
        if (!$processo) {
            return;
        }

        $arquivos = $this->getArquivos($idProcesso);
        $payload  = $this->buildPayload($processo, $arquivos);
        $this->enviar($config, $idProcesso, $payload);
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    private function getConfig(): ?array
    {
        $row = $this->db->query("SELECT * FROM webhook_config ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function getProcesso(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, numero_processo, cod_api, tribunal, tipo_sistema,
                   status_consulta, qtd_atas, data_ultima_consulta
            FROM processos WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function getArquivos(int $idProcesso): array
    {
        $stmt = $this->db->prepare("
            SELECT id, nome_arquivo, formato, tamanho_bytes
            FROM processos_arquivos WHERE id_processo = ?
            ORDER BY id ASC
        ");
        $stmt->execute([$idProcesso]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buildPayload(array $processo, array $arquivos): array
    {
        $arquivosPayload = array_map(function (array $a) {
            return [
                'id'            => (int)$a['id'],
                'nome'          => $a['nome_arquivo'],
                'formato'       => $a['formato'],
                'tamanho_bytes' => (int)$a['tamanho_bytes'],
                'url'           => $this->apiBase . '/?endpoint=download_arquivo_id&id=' . $a['id'],
            ];
        }, $arquivos);

        return [
            'evento'          => $processo['status_consulta'],
            'id_integracao'   => $processo['cod_api'] ?? null,
            'numero_processo' => $processo['numero_processo'],
            'status'          => $processo['status_consulta'],
            'tribunal'        => $processo['tribunal'],
            'tipo_sistema'    => $processo['tipo_sistema'],
            'qtd_atas'        => (int)$processo['qtd_atas'],
            'data_consulta'   => $processo['data_ultima_consulta'],
            'arquivos'        => $arquivosPayload,
        ];
    }

    /**
     * Envia o POST e grava o resultado em webhook_logs.
     * Também usado pelo reenvio via painel.
     */
    public function enviar(array $config, int $idProcesso, array $payload): void
    {
        $url        = $config['url'];
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $headers = [
            'Content-Type: application/json',
            'X-Webhook-Event: ' . ($payload['evento'] ?? ''),
        ];
        if (!empty($config['secret'])) {
            $headers[] = 'X-Webhook-Secret: ' . $config['secret'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payloadJson,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);

        $resposta   = curl_exec($ch);
        $statusHttp = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $sucesso    = $statusHttp >= 200 && $statusHttp < 300 ? 1 : 0;
        curl_close($ch);

        $this->db->prepare("
            INSERT INTO webhook_logs (id_processo, url, payload, status_http, resposta, sucesso)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([
            $idProcesso,
            $url,
            $payloadJson,
            $statusHttp ?: null,
            $resposta ?: null,
            $sucesso,
        ]);
    }

    /**
     * Reenvia um log existente usando a config atual.
     */
    public function reenviar(int $idLog): bool
    {
        $log = $this->db->prepare("SELECT * FROM webhook_logs WHERE id = ?");
        $log->execute([$idLog]);
        $row = $log->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }

        $config = $this->getConfig();
        if (!$config || empty($config['url'])) {
            return false;
        }

        $payload = json_decode($row['payload'], true) ?? [];
        $this->enviar($config, (int)$row['id_processo'], $payload);
        return true;
    }
}

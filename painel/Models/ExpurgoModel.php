<?php

class ExpurgoModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ── Consultas ──────────────────────────────────────────────────────────────

    public function getStats(): array
    {
        $row = $this->db->query("
            SELECT
                COUNT(*)                          AS total,
                COALESCE(SUM(tamanho_bytes), 0)   AS total_bytes,
                COUNT(DISTINCT id_processo)       AS processos_afetados
            FROM arquivos_expurgo
        ")->fetch(PDO::FETCH_ASSOC);

        return [
            'total'             => (int)$row['total'],
            'total_bytes'       => (int)$row['total_bytes'],
            'processos_afetados'=> (int)$row['processos_afetados'],
        ];
    }

    public function listar(array $filtros, int $pagina, int $limite): array
    {
        [$whereSql, $params] = $this->buildWhere($filtros);
        $offset = ($pagina - 1) * $limite;

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM arquivos_expurgo {$whereSql}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT id, id_processo, numero_processo, nome_arquivo,
                   caminho_arquivo, formato, tamanho_bytes, baixado_em, deletado_em, motivo
            FROM arquivos_expurgo
            {$whereSql}
            ORDER BY deletado_em DESC
            LIMIT {$limite} OFFSET {$offset}
        ");
        $stmt->execute($params);

        return [
            'total'   => $total,
            'paginas' => (int)ceil($total / $limite) ?: 1,
            'dados'   => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    // ── Arquivos elegíveis para expurgo (download_ok=1, criado_em >= 10 dias) ──

    public function getElegiveis(int $dias = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT a.id, a.id_processo, a.nome_arquivo, a.caminho_arquivo,
                   a.formato, a.tamanho_bytes, a.criado_em,
                   p.numero_processo
            FROM processos_arquivos a
            JOIN processos p ON p.id = a.id_processo
            WHERE a.download_ok = 1
              AND a.criado_em <= NOW() - INTERVAL ? DAY
              AND a.caminho_arquivo IS NOT NULL
              AND a.caminho_arquivo != ''
            ORDER BY a.criado_em ASC
        ");
        $stmt->execute([$dias]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registrarExpurgo(array $arquivo, string $motivo = 'expurgo_automatico_10d'): void
    {
        $this->db->prepare("
            INSERT INTO arquivos_expurgo
                (id_processo, numero_processo, nome_arquivo, caminho_arquivo,
                 formato, tamanho_bytes, baixado_em, motivo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $arquivo['id_processo'],
            $arquivo['numero_processo'] ?? '',
            $arquivo['nome_arquivo']    ?? null,
            $arquivo['caminho_arquivo'],
            $arquivo['formato']         ?? null,
            $arquivo['tamanho_bytes']   ?? 0,
            $arquivo['criado_em']       ?? null,
            $motivo,
        ]);
    }

    public function removerDaTabela(int $idArquivo): void
    {
        $this->db->prepare("DELETE FROM processos_arquivos WHERE id = ?")
                 ->execute([$idArquivo]);
    }

    // ── helpers ────────────────────────────────────────────────────────────────

    private function buildWhere(array $filtros): array
    {
        $where  = [];
        $params = [];

        if (!empty($filtros['search'])) {
            $where[]  = "numero_processo LIKE ?";
            $params[] = "%{$filtros['search']}%";
        }
        if (!empty($filtros['data_de'])) {
            $where[]  = "DATE(deletado_em) >= ?";
            $params[] = $filtros['data_de'];
        }
        if (!empty($filtros['data_ate'])) {
            $where[]  = "DATE(deletado_em) <= ?";
            $params[] = $filtros['data_ate'];
        }

        $whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";
        return [$whereSql, $params];
    }
}

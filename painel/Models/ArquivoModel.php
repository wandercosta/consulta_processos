<?php

class ArquivoModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getStats(): array
    {
        return [
            'total'       => (int)$this->db->query("SELECT COUNT(*) FROM processos_arquivos")->fetchColumn(),
            'total_ok'    => (int)$this->db->query("SELECT COUNT(*) FROM processos_arquivos WHERE download_ok = 1")->fetchColumn(),
            'total_falhou'=> (int)$this->db->query("SELECT COUNT(*) FROM processos_arquivos WHERE download_ok = 0")->fetchColumn(),
            'total_bytes' => (int)$this->db->query("SELECT COALESCE(SUM(tamanho_bytes),0) FROM processos_arquivos WHERE download_ok = 1")->fetchColumn(),
        ];
    }

    public function listar(array $filtros, int $pagina, int $limite): array
    {
        [$whereSql, $params] = $this->buildWhere($filtros);
        $offset = ($pagina - 1) * $limite;

        $countStmt = $this->db->prepare("
            SELECT COUNT(*) FROM processos_arquivos a
            JOIN processos p ON p.id = a.id_processo
            {$whereSql}
        ");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT a.id, a.id_processo, a.nome_arquivo, a.caminho_arquivo,
                   a.formato, a.tamanho_bytes, a.texto_doc, a.indice,
                   a.download_ok, a.criado_em,
                   p.numero_processo
            FROM processos_arquivos a
            JOIN processos p ON p.id = a.id_processo
            {$whereSql}
            ORDER BY a.criado_em DESC
            LIMIT {$limite} OFFSET {$offset}
        ");
        $stmt->execute($params);

        return [
            'total'   => $total,
            'paginas' => (int)ceil($total / $limite),
            'dados'   => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    public function findByProcesso(int $idProcesso): array
    {
        $stmt = $this->db->prepare("
            SELECT id, nome_arquivo, caminho_arquivo, formato, tamanho_bytes,
                   texto_doc, indice, download_ok, criado_em
            FROM processos_arquivos
            WHERE id_processo = ?
            ORDER BY indice ASC
        ");
        $stmt->execute([$idProcesso]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── helpers ────────────────────────────────────────────────────────────────

    private function buildWhere(array $filtros): array
    {
        $where  = [];
        $params = [];

        if (!empty($filtros['search'])) {
            $where[]  = "p.numero_processo LIKE ?";
            $params[] = "%{$filtros['search']}%";
        }
        if (!empty($filtros['formato'])) {
            $where[]  = "a.formato = ?";
            $params[] = strtoupper($filtros['formato']);
        }
        if (!empty($filtros['data_de'])) {
            $where[]  = "DATE(a.criado_em) >= ?";
            $params[] = $filtros['data_de'];
        }
        if (!empty($filtros['data_ate'])) {
            $where[]  = "DATE(a.criado_em) <= ?";
            $params[] = $filtros['data_ate'];
        }

        $whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";
        return [$whereSql, $params];
    }
}

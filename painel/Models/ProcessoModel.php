<?php

class ProcessoModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ── Dashboard ──────────────────────────────────────────────────────────────

    public function getTotaisPorStatus(): array
    {
        $stmt = $this->db->query("SELECT status_consulta, COUNT(*) as qtd FROM processos GROUP BY status_consulta");
        $totais = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $totais[$row['status_consulta']] = (int)$row['qtd'];
        }
        return $totais;
    }

    public function countReprocessando(): int
    {
        return (int)$this->db->query("
            SELECT COUNT(*) FROM processos
            WHERE status_consulta = 'FINALIZADO SEM ATA'
              AND data_ultima_consulta >= NOW() - INTERVAL 10 MINUTE
        ")->fetchColumn();
    }

    public function getProximosDaFila(): array
    {
        return $this->db->query("
            SELECT id, numero_processo, status_consulta, criado_em FROM processos
            WHERE status_consulta = 'PENDENTE'
               OR (status_consulta = 'FINALIZADO SEM ATA' AND data_ultima_consulta < NOW() - INTERVAL 10 MINUTE)
            ORDER BY CASE status_consulta WHEN 'PENDENTE' THEN 0 ELSE 1 END ASC, criado_em ASC
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUltimosProcessados(): array
    {
        return $this->db->query("
            SELECT id, numero_processo, status_consulta, data_ultima_consulta FROM processos
            WHERE status_consulta IN ('FINALIZADO COM ATA', 'FINALIZADO SEM ATA', 'FINALIZADO')
            ORDER BY data_ultima_consulta DESC
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUltimosCadastrados(): array
    {
        return $this->db->query("
            SELECT id, numero_processo, status_consulta, criado_em FROM processos
            ORDER BY criado_em DESC LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUltimosLogs(): array
    {
        return $this->db->query("
            SELECT pl.mensagem, pl.status, pl.criado_em, p.numero_processo
            FROM processos_logs pl
            JOIN processos p ON p.id = pl.id_processo
            ORDER BY pl.criado_em DESC LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Listagem ───────────────────────────────────────────────────────────────

    public function listar(array $filtros, int $pagina, int $limite): array
    {
        [$whereSql, $params] = $this->buildWhere($filtros);
        $offset = ($pagina - 1) * $limite;

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM processos {$whereSql}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT id, numero_processo, status_consulta, possui_ata, qtd_atas,
                   data_ultima_consulta, criado_em, mensagem_erro
            FROM processos {$whereSql}
            ORDER BY criado_em DESC
            LIMIT {$limite} OFFSET {$offset}
        ");
        $stmt->execute($params);

        return [
            'total'   => $total,
            'paginas' => (int)ceil($total / $limite),
            'dados'   => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    // ── Detalhe ────────────────────────────────────────────────────────────────

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM processos WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getLogs(int $id): array
    {
        $stmt = $this->db->prepare("
            SELECT mensagem, status, criado_em
            FROM processos_logs
            WHERE id_processo = ?
            ORDER BY criado_em ASC
        ");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Cadastro ───────────────────────────────────────────────────────────────

    public function existeNumero(string $numero): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM processos WHERE numero_processo = ?");
        $stmt->execute([$numero]);
        return (bool)$stmt->fetch();
    }

    public function criar(string $numero, string $tribunal): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO processos (numero_processo, tribunal, status_consulta, criado_em)
            VALUES (?, ?, 'PENDENTE', NOW())
        ");
        $stmt->execute([$numero, $tribunal]);
        return (int)$this->db->lastInsertId();
    }

    // ── helpers ────────────────────────────────────────────────────────────────

    private function buildWhere(array $filtros): array
    {
        $where  = [];
        $params = [];

        if (!empty($filtros['status'])) {
            $where[]  = "status_consulta = ?";
            $params[] = $filtros['status'];
        }
        if (!empty($filtros['search'])) {
            $where[]  = "numero_processo LIKE ?";
            $params[] = "%{$filtros['search']}%";
        }
        if (!empty($filtros['possui_ata'])) {
            $where[]  = "possui_ata = ?";
            $params[] = $filtros['possui_ata'];
        }
        if (!empty($filtros['data_de'])) {
            $where[]  = "DATE(criado_em) >= ?";
            $params[] = $filtros['data_de'];
        }
        if (!empty($filtros['data_ate'])) {
            $where[]  = "DATE(criado_em) <= ?";
            $params[] = $filtros['data_ate'];
        }

        $whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";
        return [$whereSql, $params];
    }
}

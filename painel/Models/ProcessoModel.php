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
              AND data_ultima_consulta >= NOW() - INTERVAL 60 MINUTE
        ")->fetchColumn();
    }

    public function getProximosDaFila(): array
    {
        $max  = $this->maxTentativas();
        $stmt = $this->db->prepare("
            SELECT id, numero_processo, tribunal, tipo_sistema, data_ato, status_consulta, qtd_consultas, criado_em FROM processos
            WHERE status_consulta = 'PENDENTE'
               OR (status_consulta = 'FINALIZADO SEM ATA' AND data_ultima_consulta < NOW() - INTERVAL 60 MINUTE AND qtd_consultas < ?)
            ORDER BY CASE status_consulta WHEN 'PENDENTE' THEN 0 ELSE 1 END ASC, criado_em ASC
            LIMIT 10
        ");
        $stmt->execute([$max]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUltimosProcessados(): array
    {
        return $this->db->query("
            SELECT id, numero_processo, tribunal, tipo_sistema, data_ato, status_consulta, data_ultima_consulta FROM processos
            WHERE status_consulta IN ('FINALIZADO COM ATA', 'FINALIZADO SEM ATA', 'ESGOTADO', 'FINALIZADO')
            ORDER BY data_ultima_consulta DESC
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUltimosCadastrados(): array
    {
        return $this->db->query("
            SELECT id, numero_processo, tribunal, tipo_sistema, data_ato, status_consulta, criado_em FROM processos
            ORDER BY criado_em DESC LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getConsultando(): array
    {
        return $this->db->query("
            SELECT id, numero_processo, tribunal, tipo_sistema, data_ato, data_ultima_consulta
            FROM processos
            WHERE status_consulta = 'CONSULTANDO'
            ORDER BY data_ultima_consulta DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSemAtaAguardando(): array
    {
        $max  = $this->maxTentativas();
        $stmt = $this->db->prepare("
            SELECT id, numero_processo, tribunal, tipo_sistema, data_ato,
                   data_ultima_consulta,
                   DATE_ADD(data_ultima_consulta, INTERVAL 60 MINUTE) AS proxima_consulta,
                   qtd_consultas
            FROM processos
            WHERE status_consulta = 'FINALIZADO SEM ATA'
              AND qtd_consultas < ?
            ORDER BY proxima_consulta ASC
            LIMIT 50
        ");
        $stmt->execute([$max]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function maxTentativas(): int
    {
        try {
            $row = $this->db->query("SELECT valor FROM configuracoes WHERE chave = 'max_tentativas'")->fetch(PDO::FETCH_ASSOC);
            return $row ? max(1, (int)$row['valor']) : 10;
        } catch (\Exception $e) {
            return 10;
        }
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
            SELECT id, numero_processo, tribunal, tipo_sistema, status_consulta,
                   possui_ata, qtd_atas, data_ultima_consulta, criado_em, mensagem_erro
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

    public function findByNumero(string $numero): ?array
    {
        $stmt = $this->db->prepare("SELECT id, status_consulta FROM processos WHERE numero_processo = ?");
        $stmt->execute([$numero]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function reativarEsgotado(int $id): void
    {
        $this->db->prepare("
            UPDATE processos
            SET status_consulta = 'PENDENTE',
                qtd_consultas   = 0,
                mensagem_erro   = NULL,
                data_ultima_consulta = NOW()
            WHERE id = ?
        ")->execute([$id]);
    }

    public function criar(string $numero, string $tribunal, ?string $dataAto = null, ?string $codApi = null): int
    {
        $tipo   = self::inferirTipo($numero, $tribunal);
        $status = $tipo === 'DESCONHECIDO' ? 'NÃO COMPATÍVEL' : 'PENDENTE';
        $erro   = $tipo === 'DESCONHECIDO' ? 'Tipo de processo não reconhecido para o tribunal informado. Nenhum sistema compatível identificado.' : null;

        $stmt = $this->db->prepare("
            INSERT INTO processos (numero_processo, cod_api, tribunal, tipo_sistema, data_ato, status_consulta, mensagem_erro, criado_em)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$numero, $codApi ?: null, $tribunal, $tipo, $dataAto ?: null, $status, $erro]);
        return (int)$this->db->lastInsertId();
    }

    public static function inferirTipo(string $numero, string $tribunal): string
    {
        $digitos  = preg_replace('/\D/', '', $numero);
        $primeiro = $digitos[0] ?? '';

        if ($tribunal === 'MG') {
            if ($primeiro === '5')                    return 'PJE';
            if (in_array($primeiro, ['0', '1'], true)) return 'EPROC';
            if ($primeiro === '2')                    return 'PROCON';
        }

        return 'DESCONHECIDO';
    }

    // ── Cancelar / Recolocar ───────────────────────────────────────────────────

    public function cancelar(int $id): void
    {
        $this->db->prepare("
            UPDATE processos
            SET status_consulta = 'CANCELADO', data_ultima_consulta = NOW()
            WHERE id = ?
        ")->execute([$id]);
    }

    public function recolocar(int $id): void
    {
        $this->db->prepare("
            UPDATE processos
            SET status_consulta = 'PENDENTE', mensagem_erro = NULL, data_ultima_consulta = NOW()
            WHERE id = ?
        ")->execute([$id]);
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

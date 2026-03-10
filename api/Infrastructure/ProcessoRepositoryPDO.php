<?php

class ProcessoRepositoryPDO implements ProcessoRepositoryInterface
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findPendentes(): array
    {
        $stmt = $this->db->query("
            SELECT
                id,
                id AS id_processo,
                numero_processo,
                status_consulta,
                'TJMG' AS tribunal
            FROM processos
            WHERE status_consulta = 'PENDENTE'
               OR (
                   status_consulta        = 'FINALIZADO SEM ATA'
                   AND data_ultima_consulta < NOW() - INTERVAL 10 MINUTE
               )
            ORDER BY
                CASE status_consulta WHEN 'PENDENTE' THEN 0 ELSE 1 END ASC,
                criado_em ASC
            LIMIT 10
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM processos WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function marcarConsultando(int $id): void
    {
        $stmt = $this->db->prepare("
            UPDATE processos
            SET status_consulta = 'CONSULTANDO', data_ultima_consulta = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$id]);
    }

    public function finalizarComAta(int $id, int $qtdAtas, string $caminho): void
    {
        $stmt = $this->db->prepare("
            UPDATE processos
            SET
                status_consulta      = 'FINALIZADO COM ATA',
                possui_ata           = 'S',
                qtd_atas             = ?,
                caminho_arquivo      = ?,
                data_ultima_consulta = NOW(),
                mensagem_erro        = NULL
            WHERE id = ?
        ");
        $stmt->execute([$qtdAtas, $caminho, $id]);
    }

    public function finalizarSemAta(int $id): void
    {
        $stmt = $this->db->prepare("
            UPDATE processos
            SET
                status_consulta      = 'FINALIZADO SEM ATA',
                possui_ata           = 'N',
                qtd_atas             = 0,
                caminho_arquivo      = NULL,
                data_ultima_consulta = NOW(),
                mensagem_erro        = NULL
            WHERE id = ?
        ");
        $stmt->execute([$id]);
    }

    public function registrarErro(int $id, string $mensagem): void
    {
        $stmt = $this->db->prepare("
            UPDATE processos
            SET
                status_consulta      = 'ERRO',
                mensagem_erro        = ?,
                data_ultima_consulta = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$mensagem, $id]);
    }

    public function criar(string $numero): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO processos (numero_processo, status_consulta, criado_em)
            VALUES (?, 'PENDENTE', NOW())
        ");
        $stmt->execute([$numero]);
        return (int)$this->db->lastInsertId();
    }

    public function existeNumero(string $numero): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM processos WHERE numero_processo = ?");
        $stmt->execute([$numero]);
        return (bool)$stmt->fetch();
    }

    public function listar(array $filtros, int $pagina, int $limite): array
    {
        list($whereSql, $params) = $this->buildWhere($filtros);
        $offset = ($pagina - 1) * $limite;

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM processos {$whereSql}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT id, numero_processo, status_consulta, possui_ata, qtd_atas,
                   caminho_arquivo, data_ultima_consulta, criado_em, mensagem_erro
            FROM processos {$whereSql}
            ORDER BY criado_em DESC
            LIMIT {$limite} OFFSET {$offset}
        ");
        $stmt->execute($params);

        return [
            'total'   => $total,
            'pagina'  => $pagina,
            'limite'  => $limite,
            'paginas' => (int)ceil($total / $limite),
            'dados'   => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    public function inserirLog(int $idProcesso, string $mensagem, string $status): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO processos_logs (id_processo, mensagem, status)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$idProcesso, $mensagem, $status]);
    }

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

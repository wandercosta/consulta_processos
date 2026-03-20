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
        $max  = $this->maxTentativas();
        $stmt = $this->db->prepare("
            SELECT
                id,
                id AS id_processo,
                numero_processo,
                status_consulta,
                tribunal,
                tipo_sistema,
                data_ato,
                qtd_consultas
            FROM processos
            WHERE (
                status_consulta = 'PENDENTE'
                OR (
                    status_consulta        = 'FINALIZADO SEM ATA'
                    AND data_ultima_consulta < NOW() - INTERVAL 60 MINUTE
                    AND qtd_consultas       < ?
                )
            )
            AND (data_ato IS NULL OR data_ato <= CURDATE())
            ORDER BY
                CASE status_consulta WHEN 'PENDENTE' THEN 0 ELSE 1 END ASC,
                criado_em ASC
            LIMIT 10
        ");
        $stmt->execute([$max]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function marcarNaoCompativel(int $id, string $mensagem): void
    {
        $this->db->prepare("
            UPDATE processos
            SET status_consulta = 'NÃO COMPATÍVEL', mensagem_erro = ?, data_ultima_consulta = NOW()
            WHERE id = ?
        ")->execute([$mensagem, $id]);
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
        $max  = $this->maxTentativas();
        $msg  = "Limite de {$max} consultas atingido. Processo não será reprocessado automaticamente.";
        $stmt = $this->db->prepare("
            UPDATE processos
            SET
                status_consulta      = CASE WHEN qtd_consultas + 1 >= ? THEN 'ESGOTADO' ELSE 'FINALIZADO SEM ATA' END,
                possui_ata           = 'N',
                qtd_atas             = 0,
                qtd_consultas        = qtd_consultas + 1,
                caminho_arquivo      = NULL,
                data_ultima_consulta = NOW(),
                mensagem_erro        = CASE WHEN qtd_consultas + 1 >= ? THEN ? ELSE NULL END
            WHERE id = ?
        ");
        $stmt->execute([$max, $max, $msg, $id]);
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

    public function criar(string $numero, string $tribunal, ?string $dataAto = null, ?string $codApi = null): int
    {
        $tipo   = self::inferirTipo($numero, $tribunal);
        $status = $tipo === 'DESCONHECIDO' ? 'ERRO' : 'PENDENTE';
        $erro   = $tipo === 'DESCONHECIDO' ? 'Tipo de processo não identificado para o tribunal informado.' : null;

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

    private function maxTentativas(): int
    {
        try {
            $row = $this->db->query("SELECT valor FROM configuracoes WHERE chave = 'max_tentativas'")->fetch(PDO::FETCH_ASSOC);
            return $row ? max(1, (int)$row['valor']) : 10;
        } catch (\Exception $e) {
            return 10;
        }
    }

    public function existeNumero(string $numero): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM processos WHERE numero_processo = ?");
        $stmt->execute([$numero]);
        return (bool)$stmt->fetch();
    }

    public function findByNumero(string $numero): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM processos WHERE numero_processo = ?");
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

    public function getConfiguracoes(): array
    {
        try {
            $rows = $this->db->query("SELECT chave, valor FROM configuracoes")->fetchAll(PDO::FETCH_ASSOC);
            $cfg  = [];
            foreach ($rows as $r) {
                $cfg[$r['chave']] = $r['valor'];
            }
            return $cfg;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function listar(array $filtros, int $pagina, int $limite): array
    {
        list($whereSql, $params) = $this->buildWhere($filtros);
        $offset = ($pagina - 1) * $limite;

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM processos {$whereSql}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT id, numero_processo, tribunal, tipo_sistema, status_consulta,
                   possui_ata, qtd_atas, caminho_arquivo, data_ultima_consulta,
                   criado_em, mensagem_erro
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

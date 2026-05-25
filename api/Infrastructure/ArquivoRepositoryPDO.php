<?php

class ArquivoRepositoryPDO implements ArquivoRepositoryInterface
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function criar(array $dados): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO processos_arquivos
                (id_processo, nome_arquivo, caminho_arquivo, formato,
                 tamanho_bytes, texto_doc, indice, download_ok)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $dados['id_processo'],
            $dados['nome_arquivo'],
            $dados['caminho_arquivo'] ?? null,
            isset($dados['formato']) ? strtoupper($dados['formato']) : null,
            $dados['tamanho_bytes']  ?? null,
            $dados['texto_doc']      ?? null,
            (int)($dados['indice']   ?? 1),
            $dados['download_ok']    ? 1 : 0,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT a.caminho_arquivo, a.nome_arquivo, a.formato
            FROM processos_arquivos a
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findCaminhoByProcesso(int $idProcesso): ?array
    {
        $stmt = $this->db->prepare("
            SELECT numero_processo, caminho_arquivo
            FROM processos
            WHERE id = ?
        ");
        $stmt->execute([$idProcesso]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateCaminho(int $id, string $caminho): void
    {
        $stmt = $this->db->prepare("
            UPDATE processos_arquivos SET caminho_arquivo = ? WHERE id = ?
        ");
        $stmt->execute([$caminho, $id]);
    }

    public function getElegiveisExpurgo(int $dias): array
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

    public function registrarExpurgo(array $arq): void
    {
        $this->db->prepare("
            INSERT INTO arquivos_expurgo
                (id_processo, numero_processo, nome_arquivo, caminho_arquivo,
                 formato, tamanho_bytes, baixado_em, motivo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $arq['id_processo']      ?? 0,
            $arq['numero_processo']  ?? '',
            $arq['nome_arquivo']     ?? null,
            $arq['caminho_arquivo'],
            $arq['formato']          ?? null,
            $arq['tamanho_bytes']    ?? 0,
            $arq['criado_em']        ?? null,
            $arq['motivo']           ?? 'expurgo_automatico_10d',
        ]);
        // Remove da tabela de arquivos
        $this->db->prepare("DELETE FROM processos_arquivos WHERE id = ?")
                 ->execute([$arq['id']]);
    }

    public function getExtensoes(): array
    {
        try {
            $row = $this->db->query(
                "SELECT valor FROM configuracoes WHERE chave = 'extensoes_aceitas'"
            )->fetch(PDO::FETCH_ASSOC);
            if (!$row) return ['pdf', 'html'];
            return array_values(array_filter(array_map('trim', explode(',', $row['valor']))));
        } catch (\Exception $e) {
            return ['pdf', 'html']; // fallback seguro
        }
    }
}

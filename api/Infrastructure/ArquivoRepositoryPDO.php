<?php

class ArquivoRepositoryPDO implements ArquivoRepositoryInterface
{
    public function __construct(private PDO $db) {}

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
}

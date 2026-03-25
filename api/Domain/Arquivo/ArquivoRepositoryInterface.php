<?php

interface ArquivoRepositoryInterface
{
    /** Registra um arquivo na tabela processos_arquivos — retorna ID inserido */
    public function criar(array $dados): int;

    /** Busca arquivo por ID (da tabela processos_arquivos) */
    public function findById(int $id): ?array;

    /** Busca caminho do arquivo pelo ID do processo (coluna caminho_arquivo em processos) */
    public function findCaminhoByProcesso(int $idProcesso): ?array;

    /** Atualiza o caminho do arquivo no servidor após upload */
    public function updateCaminho(int $id, string $caminho): void;

    /** Retorna lista de extensões aceitas (ex: ['pdf', 'html']) lida de configuracoes */
    public function getExtensoes(): array;
}

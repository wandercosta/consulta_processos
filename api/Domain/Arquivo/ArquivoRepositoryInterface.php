<?php

interface ArquivoRepositoryInterface
{
    /** Registra um arquivo na tabela processos_arquivos — retorna ID inserido */
    public function criar(array $dados): int;

    /** Busca arquivo por ID (da tabela processos_arquivos) */
    public function findById(int $id): ?array;

    /** Busca caminho do arquivo pelo ID do processo (coluna caminho_arquivo em processos) */
    public function findCaminhoByProcesso(int $idProcesso): ?array;
}

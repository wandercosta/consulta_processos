<?php

interface ProcessoRepositoryInterface
{
    /** Retorna processos PENDENTE + FINALIZADO SEM ATA expirados, limitado a 10 */
    public function findPendentes(): array;

    /** Retorna dados completos de um processo por ID */
    public function findById(int $id): ?array;

    /** Muda status para CONSULTANDO */
    public function marcarConsultando(int $id): void;

    /** Finaliza processo com ATA */
    public function finalizarComAta(int $id, int $qtdAtas, string $caminho): void;

    /** Finaliza processo sem ATA */
    public function finalizarSemAta(int $id): void;

    /** Registra erro no processo */
    public function registrarErro(int $id, string $mensagem): void;

    /** Marca processo como NÃO COMPATÍVEL (sistema sem scraper implementado) */
    public function marcarNaoCompativel(int $id, string $mensagem): void;

    /** Cria novo processo — retorna ID inserido */
    public function criar(string $numero, string $tribunal, ?string $dataAto = null): int;

    /** Verifica se número de processo já existe */
    public function existeNumero(string $numero): bool;

    /** Listagem paginada com filtros — retorna array com dados e metadados */
    public function listar(array $filtros, int $pagina, int $limite): array;

    /** Insere entrada no histórico de logs */
    public function inserirLog(int $idProcesso, string $mensagem, string $status): void;
}

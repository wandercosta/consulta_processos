<?php

class Arquivo
{
    public function __construct(
        public readonly int     $id,
        public readonly int     $id_processo,
        public readonly string  $nome_arquivo,
        public readonly ?string $caminho_arquivo = null,
        public readonly ?string $formato         = null,
        public readonly ?int    $tamanho_bytes   = null,
        public readonly ?string $texto_doc       = null,
        public readonly int     $indice          = 1,
        public readonly int     $download_ok     = 1,
        public readonly string  $criado_em       = '',
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id:              (int)$row['id'],
            id_processo:     (int)$row['id_processo'],
            nome_arquivo:    $row['nome_arquivo'],
            caminho_arquivo: $row['caminho_arquivo'] ?? null,
            formato:         $row['formato'] ?? null,
            tamanho_bytes:   isset($row['tamanho_bytes']) ? (int)$row['tamanho_bytes'] : null,
            texto_doc:       $row['texto_doc'] ?? null,
            indice:          (int)($row['indice'] ?? 1),
            download_ok:     (int)($row['download_ok'] ?? 1),
            criado_em:       $row['criado_em'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'id_processo'     => $this->id_processo,
            'nome_arquivo'    => $this->nome_arquivo,
            'caminho_arquivo' => $this->caminho_arquivo,
            'formato'         => $this->formato,
            'tamanho_bytes'   => $this->tamanho_bytes,
            'texto_doc'       => $this->texto_doc,
            'indice'          => $this->indice,
            'download_ok'     => $this->download_ok,
            'criado_em'       => $this->criado_em,
        ];
    }
}

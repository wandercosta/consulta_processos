<?php

class Arquivo
{
    public int     $id;
    public int     $id_processo;
    public string  $nome_arquivo;
    public ?string $caminho_arquivo;
    public ?string $formato;
    public ?int    $tamanho_bytes;
    public ?string $texto_doc;
    public int     $indice;
    public int     $download_ok;
    public string  $criado_em;

    public function __construct(
        int     $id,
        int     $id_processo,
        string  $nome_arquivo,
        ?string $caminho_arquivo = null,
        ?string $formato         = null,
        ?int    $tamanho_bytes   = null,
        ?string $texto_doc       = null,
        int     $indice          = 1,
        int     $download_ok     = 1,
        string  $criado_em       = ''
    ) {
        $this->id              = $id;
        $this->id_processo     = $id_processo;
        $this->nome_arquivo    = $nome_arquivo;
        $this->caminho_arquivo = $caminho_arquivo;
        $this->formato         = $formato;
        $this->tamanho_bytes   = $tamanho_bytes;
        $this->texto_doc       = $texto_doc;
        $this->indice          = $indice;
        $this->download_ok     = $download_ok;
        $this->criado_em       = $criado_em;
    }

    public static function fromArray(array $row): self
    {
        return new self(
            (int)$row['id'],
            (int)$row['id_processo'],
            $row['nome_arquivo'],
            $row['caminho_arquivo'] ?? null,
            $row['formato']         ?? null,
            isset($row['tamanho_bytes']) ? (int)$row['tamanho_bytes'] : null,
            $row['texto_doc']       ?? null,
            (int)($row['indice']    ?? 1),
            (int)($row['download_ok'] ?? 1),
            $row['criado_em']       ?? ''
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

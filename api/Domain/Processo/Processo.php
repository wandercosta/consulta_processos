<?php

class Processo
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $numero_processo,
        public readonly string  $status_consulta,
        public readonly ?string $possui_ata          = null,
        public readonly ?int    $qtd_atas            = null,
        public readonly ?string $caminho_arquivo     = null,
        public readonly ?string $data_ultima_consulta = null,
        public readonly string  $criado_em           = '',
        public readonly ?string $mensagem_erro       = null,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id:                   (int)$row['id'],
            numero_processo:      $row['numero_processo'],
            status_consulta:      $row['status_consulta'],
            possui_ata:           $row['possui_ata'] ?? null,
            qtd_atas:             isset($row['qtd_atas']) ? (int)$row['qtd_atas'] : null,
            caminho_arquivo:      $row['caminho_arquivo'] ?? null,
            data_ultima_consulta: $row['data_ultima_consulta'] ?? null,
            criado_em:            $row['criado_em'] ?? '',
            mensagem_erro:        $row['mensagem_erro'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'                   => $this->id,
            'numero_processo'      => $this->numero_processo,
            'status_consulta'      => $this->status_consulta,
            'possui_ata'           => $this->possui_ata,
            'qtd_atas'             => $this->qtd_atas,
            'caminho_arquivo'      => $this->caminho_arquivo,
            'data_ultima_consulta' => $this->data_ultima_consulta,
            'criado_em'            => $this->criado_em,
            'mensagem_erro'        => $this->mensagem_erro,
        ];
    }
}

<?php

class Processo
{
    public int     $id;
    public string  $numero_processo;
    public string  $status_consulta;
    public ?string $possui_ata;
    public ?int    $qtd_atas;
    public ?string $caminho_arquivo;
    public ?string $data_ultima_consulta;
    public string  $criado_em;
    public ?string $mensagem_erro;

    public function __construct(
        int     $id,
        string  $numero_processo,
        string  $status_consulta,
        ?string $possui_ata           = null,
        ?int    $qtd_atas             = null,
        ?string $caminho_arquivo      = null,
        ?string $data_ultima_consulta = null,
        string  $criado_em            = '',
        ?string $mensagem_erro        = null
    ) {
        $this->id                   = $id;
        $this->numero_processo      = $numero_processo;
        $this->status_consulta      = $status_consulta;
        $this->possui_ata           = $possui_ata;
        $this->qtd_atas             = $qtd_atas;
        $this->caminho_arquivo      = $caminho_arquivo;
        $this->data_ultima_consulta = $data_ultima_consulta;
        $this->criado_em            = $criado_em;
        $this->mensagem_erro        = $mensagem_erro;
    }

    public static function fromArray(array $row): self
    {
        return new self(
            (int)$row['id'],
            $row['numero_processo'],
            $row['status_consulta'],
            $row['possui_ata']           ?? null,
            isset($row['qtd_atas'])      ? (int)$row['qtd_atas'] : null,
            $row['caminho_arquivo']      ?? null,
            $row['data_ultima_consulta'] ?? null,
            $row['criado_em']            ?? '',
            $row['mensagem_erro']        ?? null
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

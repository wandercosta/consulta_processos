<?php

class ProcessoController
{
    private ProcessoRepositoryInterface $repo;

    public function __construct(ProcessoRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function pendentes(): void
    {
        echo json_encode($this->repo->findPendentes());
    }

    public function registrarConsulta(): void
    {
        $data = $this->input();
        $id   = (int)($data['id_processo'] ?? 0);

        if (!$id) {
            $this->erro400("id_processo é obrigatório");
        }

        $this->repo->marcarConsultando($id);
        echo json_encode(["status" => "ok"]);
    }

    public function registrarAta(): void
    {
        $data    = $this->input();
        $id      = (int)($data['id_processo'] ?? 0);
        $qtd     = (int)($data['qtd_atas']    ?? 0);
        $caminho = $data['arquivo']            ?? '';

        if (!$id) {
            $this->erro400("id_processo é obrigatório");
        }

        $this->repo->finalizarComAta($id, $qtd, $caminho);
        echo json_encode(["status" => "ata registrada"]);
    }

    public function registrarSemAta(): void
    {
        $data = $this->input();
        $id   = (int)($data['id_processo'] ?? 0);

        if (!$id) {
            $this->erro400("id_processo é obrigatório");
        }

        $this->repo->finalizarSemAta($id);
        echo json_encode(["status" => "processo finalizado sem ata"]);
    }

    public function registrarErro(): void
    {
        $data     = $this->input();
        $id       = (int)($data['id_processo']  ?? 0);
        $mensagem = $data['mensagem_erro'] ?? 'Erro não informado';

        if (!$id) {
            $this->erro400("id_processo é obrigatório");
        }

        $this->repo->registrarErro($id, $mensagem);
        echo json_encode(["status" => "erro registrado"]);
    }

    public function status(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            $this->erro400("id é obrigatório");
        }

        $processo = $this->repo->findById($id);

        if (!$processo) {
            http_response_code(404);
            echo json_encode(["erro" => "Processo não encontrado"]);
            exit;
        }

        echo json_encode($processo);
    }

    public function logs(): void
    {
        $data     = $this->input();
        $id       = (int)($data['id_processo'] ?? 0);
        $mensagem = $data['mensagem']           ?? '';
        $status   = $data['status']             ?? '';

        if (!$id || !$mensagem || !$status) {
            $this->erro400("id_processo, mensagem e status são obrigatórios");
        }

        $this->repo->inserirLog($id, $mensagem, $status);
        echo json_encode(["status" => "log salvo"]);
    }

    public function cadastrar(): void
    {
        $data     = $this->input();
        $numero   = trim($data['numero_processo'] ?? '');
        $tribunal = strtoupper(trim($data['tribunal'] ?? 'TJMG'));
        $dataAto  = $data['data_ato'] ?? null;

        if ($dataAto !== null && $dataAto !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataAto)) {
            $this->erro400("data_ato deve estar no formato YYYY-MM-DD");
        }

        if ($numero === '') {
            $this->erro400("numero_processo é obrigatório");
        }

        if ($this->repo->existeNumero($numero)) {
            http_response_code(409);
            echo json_encode(["erro" => "Processo já cadastrado"]);
            exit;
        }

        $id = $this->repo->criar($numero, $tribunal, $dataAto ?: null);
        echo json_encode(["status" => "processo cadastrado", "id" => $id]);
    }

    public function listar(): void
    {
        $filtros = [
            'status'     => $_GET['status']      ?? '',
            'search'     => $_GET['search']      ?? '',
            'possui_ata' => $_GET['possui_ata']  ?? '',
            'data_de'    => $_GET['data_de']     ?? '',
            'data_ate'   => $_GET['data_ate']    ?? '',
        ];
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $limite = min(100, max(1, (int)($_GET['limite'] ?? 20)));

        echo json_encode($this->repo->listar($filtros, $pagina, $limite));
    }

    // ── helpers ────────────────────────────────────────────────────────────────

    private function input(): array
    {
        return json_decode(file_get_contents("php://input"), true) ?? [];
    }

    private function erro400(string $mensagem): void
    {
        http_response_code(400);
        echo json_encode(["erro" => $mensagem]);
        exit;
    }
}

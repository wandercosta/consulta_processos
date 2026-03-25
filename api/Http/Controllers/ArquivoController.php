<?php

class ArquivoController
{
    private const MIME_TYPES = [
        'pdf'  => 'application/pdf',
        'html' => 'text/html',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'doc'  => 'application/msword',
        'zip'  => 'application/zip',
    ];

    private ArquivoRepositoryInterface $repo;

    public function __construct(ArquivoRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function registrar(): void
    {
        $data = json_decode(file_get_contents("php://input"), true) ?? [];

        $idProcesso  = $data['id_processo']  ?? null;
        $nomeArquivo = $data['nome_arquivo'] ?? null;

        if (!$idProcesso || !$nomeArquivo) {
            http_response_code(400);
            echo json_encode(["erro" => "id_processo e nome_arquivo são obrigatórios"]);
            exit;
        }

        // Valida extensão contra as configuradas no painel
        $formato = strtolower(trim($data['formato'] ?? ''));
        if ($formato !== '') {
            $permitidas = array_map('strtolower', $this->repo->getExtensoes());
            if (!in_array($formato, $permitidas, true)) {
                echo json_encode([
                    "ignorado" => true,
                    "motivo"   => "Extensão '{$formato}' não está na lista de aceitas: " . implode(', ', $permitidas),
                ]);
                exit;
            }
        }

        $id = $this->repo->criar($data);
        echo json_encode(["status" => "arquivo registrado", "id" => $id]);
    }

    /** Download pelo ID do processo (coluna caminho_arquivo em processos) */
    public function downloadByProcesso(): void
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if (!$id) {
            http_response_code(400);
            echo json_encode(["erro" => "id do processo é obrigatório"]);
            exit;
        }

        $processo = $this->repo->findCaminhoByProcesso($id);

        if (!$processo) {
            http_response_code(404);
            echo json_encode(["erro" => "Processo não encontrado"]);
            exit;
        }

        if (empty($processo['caminho_arquivo'])) {
            http_response_code(404);
            echo json_encode(["erro" => "Este processo não possui arquivo"]);
            exit;
        }

        $caminho = $this->normalizarCaminho($processo['caminho_arquivo']);

        if (!file_exists($caminho)) {
            http_response_code(404);
            echo json_encode(["erro" => "Arquivo não encontrado no disco"]);
            exit;
        }

        $ext  = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));
        $nome = 'processo_' . preg_replace('/[^a-z0-9]/i', '_', $processo['numero_processo']) . '.' . $ext;

        $this->servirArquivo($caminho, $nome, $ext);
    }

    /** Download pelo ID da tabela processos_arquivos */
    public function downloadById(): void
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if (!$id) {
            http_response_code(400);
            echo json_encode(["erro" => "id do arquivo é obrigatório"]);
            exit;
        }

        $arquivo = $this->repo->findById($id);

        if (!$arquivo) {
            http_response_code(404);
            echo json_encode(["erro" => "Arquivo não encontrado"]);
            exit;
        }

        if (empty($arquivo['caminho_arquivo'])) {
            http_response_code(404);
            echo json_encode(["erro" => "Caminho do arquivo não registrado"]);
            exit;
        }

        $caminho = $this->normalizarCaminho($arquivo['caminho_arquivo']);

        if (!file_exists($caminho)) {
            http_response_code(404);
            echo json_encode(["erro" => "Arquivo não encontrado no disco"]);
            exit;
        }

        $ext  = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));
        $nome = $arquivo['nome_arquivo'] ?: ("arquivo_{$id}.{$ext}");

        $this->servirArquivo($caminho, $nome, $ext);
    }

    /** Visualização inline pelo ID da tabela processos_arquivos (abre no browser) */
    public function visualizarById(): void
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if (!$id) {
            http_response_code(400);
            echo json_encode(["erro" => "id do arquivo é obrigatório"]);
            exit;
        }

        $arquivo = $this->repo->findById($id);

        if (!$arquivo || empty($arquivo['caminho_arquivo'])) {
            http_response_code(404);
            echo json_encode(["erro" => "Arquivo não encontrado"]);
            exit;
        }

        $caminho = $this->normalizarCaminho($arquivo['caminho_arquivo']);

        if (!file_exists($caminho)) {
            http_response_code(404);
            echo json_encode(["erro" => "Arquivo não encontrado no disco"]);
            exit;
        }

        $ext  = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));
        $nome = $arquivo['nome_arquivo'] ?: ("arquivo_{$id}.{$ext}");
        $mime = self::MIME_TYPES[$ext] ?? 'application/octet-stream';

        while (ob_get_level()) ob_end_clean();

        header("Content-Type: {$mime}");
        header("Content-Disposition: inline; filename=\"{$nome}\"");
        header("Content-Length: " . filesize($caminho));
        header("Cache-Control: no-cache");
        readfile($caminho);
        exit;
    }

    /**
     * Recebe o arquivo via multipart/form-data, salva no VPS e atualiza o caminho no BD.
     * Parâmetros POST: id_arquivo (int)
     * Arquivo: campo "arquivo" (binário)
     */
    public function uploadArquivo(): void
    {
        $id = isset($_POST['id_arquivo']) ? (int)$_POST['id_arquivo'] : 0;

        if (!$id) {
            http_response_code(400);
            echo json_encode(["erro" => "id_arquivo é obrigatório"]);
            exit;
        }

        if (empty($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            $erro = $_FILES['arquivo']['error'] ?? -1;
            http_response_code(400);
            echo json_encode(["erro" => "Arquivo inválido ou não enviado", "codigo" => $erro]);
            exit;
        }

        // Valida extensão do arquivo enviado contra as extensões configuradas
        $extEnviada = strtolower(pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION));
        $permitidas = array_map('strtolower', $this->repo->getExtensoes());
        if ($extEnviada !== '' && !in_array($extEnviada, $permitidas, true)) {
            echo json_encode([
                "ignorado" => true,
                "motivo"   => "Extensão '{$extEnviada}' não está na lista de aceitas: " . implode(', ', $permitidas),
            ]);
            exit;
        }

        $registro = $this->repo->findById($id);
        if (!$registro) {
            http_response_code(404);
            echo json_encode(["erro" => "Registro de arquivo não encontrado (id={$id})"]);
            exit;
        }

        // Resolve diretório de uploads
        $uploadsPath = Env::get('UPLOADS_PATH', 'uploads');
        if ($uploadsPath !== '' && $uploadsPath[0] === '/') {
            $baseDir = rtrim($uploadsPath, '/');
        } else {
            // Relativo à raiz do projeto: sobe 3 níveis a partir de api/Http/Controllers/
            $baseDir = rtrim(dirname(__DIR__, 3) . '/' . trim($uploadsPath, '/'), '/');
        }

        $subDir = $baseDir . '/' . strtolower($registro['formato'] ?? 'files');
        if (!is_dir($subDir) && !mkdir($subDir, 0755, true) && !is_dir($subDir)) {
            http_response_code(500);
            echo json_encode(["erro" => "Não foi possível criar o diretório de upload"]);
            exit;
        }

        $nomeSeguro = preg_replace('/[^a-zA-Z0-9._-]/', '_', $registro['nome_arquivo'] ?: "arquivo_{$id}");
        $destino    = $subDir . '/' . $nomeSeguro;

        if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $destino)) {
            http_response_code(500);
            echo json_encode(["erro" => "Falha ao salvar o arquivo no servidor"]);
            exit;
        }

        $this->repo->updateCaminho($id, $destino);

        echo json_encode(["status" => "arquivo salvo", "caminho" => $destino]);
    }

    // ── helpers ────────────────────────────────────────────────────────────────

    private function normalizarCaminho(string $caminho): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $caminho);
    }

    private function servirArquivo(string $caminho, string $nome, string $ext): void
    {
        while (ob_get_level()) ob_end_clean();

        $mime = self::MIME_TYPES[$ext] ?? 'application/octet-stream';

        header("Content-Type: {$mime}");
        header("Content-Disposition: attachment; filename=\"{$nome}\"");
        header("Content-Length: " . filesize($caminho));
        header("Cache-Control: no-cache");
        readfile($caminho);
        exit;
    }
}

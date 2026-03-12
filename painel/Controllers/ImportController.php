<?php

require_once PAINEL_ROOT . '/lib/XlsxReader.php';

class ImportController extends BaseController
{
    private ProcessoModel $model;

    public function __construct(ProcessoModel $model)
    {
        $this->model = $model;
    }

    // ── Formulário de upload ────────────────────────────────────────────────────

    public function index(): void
    {
        $this->render('processos/importar', [
            'paginaAtual'  => 'importar',
            'tituloPagina' => 'Importar Planilha',
            'resultados'   => null,
            'erroUpload'   => null,
        ]);
    }

    // ── Processamento do arquivo ────────────────────────────────────────────────

    public function processar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['planilha']['tmp_name'])) {
            $this->redirect('importar');
        }

        $file    = $_FILES['planilha'];
        $tmpPath = $file['tmp_name'];
        $origName = strtolower($file['name']);

        // Valida extensão
        $ext = pathinfo($origName, PATHINFO_EXTENSION);
        if (!in_array($ext, ['xlsx', 'csv'], true)) {
            $this->render('processos/importar', [
                'paginaAtual'  => 'importar',
                'tituloPagina' => 'Importar Planilha',
                'resultados'   => null,
                'erroUpload'   => 'Formato inválido. Envie um arquivo .xlsx ou .csv.',
            ]);
            return;
        }

        try {
            $linhas = $ext === 'csv'
                ? $this->parseCsv($tmpPath)
                : XlsxReader::read($tmpPath);
        } catch (Throwable $e) {
            $this->render('processos/importar', [
                'paginaAtual'  => 'importar',
                'tituloPagina' => 'Importar Planilha',
                'resultados'   => null,
                'erroUpload'   => 'Erro ao ler o arquivo: ' . $e->getMessage(),
            ]);
            return;
        }

        if (empty($linhas)) {
            $this->render('processos/importar', [
                'paginaAtual'  => 'importar',
                'tituloPagina' => 'Importar Planilha',
                'resultados'   => null,
                'erroUpload'   => 'O arquivo está vazio.',
            ]);
            return;
        }

        // Detecta e remove cabeçalho (se a primeira linha não tem número de processo)
        $cabecalho = array_map('strtolower', array_map('trim', $linhas[0]));
        if ($this->isCabecalho($cabecalho)) {
            array_shift($linhas);
        }

        // Identifica índices das colunas pelo cabeçalho detectado (ou assume ordem fixa)
        $mapa = $this->mapearColunas($cabecalho);

        // Importa linha a linha
        $resultados = [];
        foreach ($linhas as $i => $linha) {
            $resultados[] = $this->importarLinha($linha, $mapa, $i + 1);
        }

        $this->render('processos/importar', [
            'paginaAtual'  => 'importar',
            'tituloPagina' => 'Importar Planilha',
            'resultados'   => $resultados,
            'erroUpload'   => null,
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function parseCsv(string $path): array
    {
        $rows = [];
        if (($fh = fopen($path, 'r')) === false) {
            throw new RuntimeException('Não foi possível abrir o CSV.');
        }

        // Detecta delimitador: ; ou ,
        $amostra    = fread($fh, 4096);
        $delimitador = (substr_count($amostra, ';') >= substr_count($amostra, ',')) ? ';' : ',';
        rewind($fh);

        while (($row = fgetcsv($fh, 0, $delimitador)) !== false) {
            $rows[] = $row;
        }
        fclose($fh);
        return $rows;
    }

    private function isCabecalho(array $linha): bool
    {
        // Se a primeira célula contém palavras típicas de cabeçalho
        $primeira = $linha[0] ?? '';
        return in_array($primeira, ['processo', 'numero', 'número', 'num', 'numero_processo'], true)
            || !preg_match('/\d/', $primeira);
    }

    /**
     * Mapeia nome da coluna → índice no array da linha.
     * Aceita qualquer ordem; usa fallback de ordem fixa (0=processo,1=data,2=uf,3=idapi).
     */
    private function mapearColunas(array $cabecalho): array
    {
        $sinonimos = [
            'processo'  => ['processo', 'numero', 'número', 'num', 'numero_processo', 'proc'],
            'data'      => ['data', 'data_ato', 'dt_ato', 'data ato'],
            'uf'        => ['uf', 'tribunal', 'estado', 'estado_uf'],
            'idapi'     => ['idapi', 'id_api', 'cod_api', 'codapi', 'id_integracao', 'integracao'],
        ];

        $mapa = ['processo' => 0, 'data' => 1, 'uf' => 2, 'idapi' => 3]; // fallback

        foreach ($sinonimos as $campo => $lista) {
            foreach ($lista as $sin) {
                $idx = array_search($sin, $cabecalho, true);
                if ($idx !== false) {
                    $mapa[$campo] = (int)$idx;
                    break;
                }
            }
        }

        return $mapa;
    }

    private function importarLinha(array $linha, array $mapa, int $numLinha): array
    {
        $numero = trim($linha[$mapa['processo']] ?? '');
        $data   = trim($linha[$mapa['data']]    ?? '');
        $uf     = strtoupper(trim($linha[$mapa['uf']]  ?? ''));
        $idapi  = trim($linha[$mapa['idapi']]   ?? '') ?: null;

        // Linha vazia
        if ($numero === '' && $data === '' && $uf === '') {
            return ['linha' => $numLinha, 'processo' => '—', 'status' => 'ignorada',
                    'mensagem' => 'Linha vazia ignorada.'];
        }

        if ($numero === '') {
            return ['linha' => $numLinha, 'processo' => '—', 'status' => 'erro',
                    'mensagem' => 'Número do processo vazio.'];
        }

        if ($uf === '') {
            return ['linha' => $numLinha, 'processo' => $numero, 'status' => 'erro',
                    'mensagem' => 'UF vazia.'];
        }

        // Normaliza data
        $dataFormatada = $this->normalizarData($data);
        if ($data !== '' && $dataFormatada === null) {
            return ['linha' => $numLinha, 'processo' => $numero, 'status' => 'erro',
                    'mensagem' => "Data inválida: \"{$data}\". Use DD/MM/AAAA ou AAAA-MM-DD."];
        }

        // Verifica duplicata
        if ($this->model->existeNumero($numero)) {
            return ['linha' => $numLinha, 'processo' => $numero, 'status' => 'duplicado',
                    'mensagem' => 'Processo já cadastrado. Ignorado.'];
        }

        // Importa
        try {
            $id = $this->model->criar($numero, $uf, $dataFormatada, $idapi);
            return ['linha' => $numLinha, 'processo' => $numero, 'status' => 'importado',
                    'mensagem' => "Cadastrado com ID {$id}."];
        } catch (Throwable $e) {
            return ['linha' => $numLinha, 'processo' => $numero, 'status' => 'erro',
                    'mensagem' => 'Erro ao salvar: ' . $e->getMessage()];
        }
    }

    /**
     * Converte formatos comuns para Y-m-d.
     * Aceita: DD/MM/YYYY, DD-MM-YYYY, YYYY-MM-DD, serial Excel (número).
     * Retorna null se não reconhecer.
     */
    private function normalizarData(string $valor): ?string
    {
        if ($valor === '') {
            return null;
        }

        // Já está no formato correto
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return $valor;
        }

        // DD/MM/YYYY ou DD-MM-YYYY
        if (preg_match('/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/', $valor, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        // Serial numérico do Excel (ex: 45000)
        if (is_numeric($valor) && $valor > 0) {
            return XlsxReader::excelDateToString((float)$valor) ?: null;
        }

        return null;
    }
}

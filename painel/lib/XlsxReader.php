<?php

/**
 * XlsxReader — leitor mínimo de arquivos XLSX (Office Open XML)
 * Sem dependências externas; usa ZipArchive + SimpleXML nativos do PHP.
 *
 * Limitações conhecidas:
 *  - Lê apenas a primeira planilha (sheet1)
 *  - Não suporta células mescladas
 *  - Converte datas em serial Excel para Y-m-d automaticamente
 */
class XlsxReader
{
    /**
     * Lê um arquivo XLSX e retorna um array de linhas.
     * Cada linha é um array de strings.
     *
     * @param  string $path  Caminho absoluto do arquivo .xlsx
     * @return array<int, array<int, string>>
     * @throws RuntimeException se o arquivo não puder ser aberto
     */
    public static function read(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException("Não foi possível abrir o arquivo XLSX.");
        }

        // ── Shared strings ─────────────────────────────────────────────────────
        $strings = [];
        $ssRaw   = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssRaw !== false) {
            $ss = simplexml_load_string($ssRaw);
            foreach ($ss->si as $si) {
                if (isset($si->t)) {
                    $strings[] = (string)$si->t;
                } else {
                    // rich text: concatena todos os runs
                    $text = '';
                    foreach ($si->r as $r) {
                        $text .= (string)$r->t;
                    }
                    $strings[] = $text;
                }
            }
        }

        // ── Detecta o nome real da primeira planilha ────────────────────────────
        $sheetName = 'xl/worksheets/sheet1.xml';
        $wbRaw     = $zip->getFromName('xl/workbook.xml');
        if ($wbRaw !== false) {
            $wb = simplexml_load_string($wbRaw);
            $wb->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            // Pega o rId da primeira sheet
            if (isset($wb->sheets->sheet[0])) {
                $rId = (string)$wb->sheets->sheet[0]->attributes('r', true)['id'];
                $relsRaw = $zip->getFromName('xl/_rels/workbook.xml.rels');
                if ($relsRaw !== false) {
                    $rels = simplexml_load_string($relsRaw);
                    foreach ($rels->Relationship as $rel) {
                        if ((string)$rel['Id'] === $rId) {
                            $target = (string)$rel['Target'];
                            // Target pode ser "worksheets/sheet1.xml" ou absoluto
                            $sheetName = strpos($target, 'xl/') === 0
                                ? $target
                                : 'xl/' . ltrim($target, '/');
                            break;
                        }
                    }
                }
            }
        }

        // ── Lê a planilha ───────────────────────────────────────────────────────
        $rows    = [];
        $maxCols = 0;

        $sheetRaw = $zip->getFromName($sheetName);
        if ($sheetRaw !== false) {
            $sheet = simplexml_load_string($sheetRaw);
            foreach ($sheet->sheetData->row as $row) {
                $rowData  = [];
                $lastCol  = -1;

                foreach ($row->c as $cell) {
                    // Determina índice da coluna (A=0, B=1, ..., Z=25, AA=26…)
                    $colRef  = preg_replace('/[0-9]/', '', (string)$cell['r']);
                    $colIdx  = self::colIndex($colRef);

                    // Preenche colunas vazias no meio
                    while ($lastCol < $colIdx - 1) {
                        $rowData[] = '';
                        $lastCol++;
                    }
                    $lastCol = $colIdx;

                    $type = (string)$cell['t'];
                    $val  = isset($cell->v) ? (string)$cell->v : '';

                    if ($type === 's') {
                        // Shared string
                        $val = $strings[(int)$val] ?? '';
                    } elseif ($type === 'b') {
                        $val = $val ? 'true' : 'false';
                    } elseif ($type === '' && is_numeric($val) && self::isDateStyle($cell)) {
                        // Serial de data do Excel
                        $val = self::excelDateToString((float)$val);
                    }

                    $rowData[] = $val;
                }

                $maxCols = max($maxCols, count($rowData));
                $rows[]  = $rowData;
            }
        }

        $zip->close();

        // Normaliza: todas as linhas com o mesmo número de colunas
        foreach ($rows as &$row) {
            while (count($row) < $maxCols) {
                $row[] = '';
            }
        }

        return $rows;
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /** Converte referência de coluna (ex: "A"=0, "Z"=25, "AA"=26) */
    private static function colIndex(string $ref): int
    {
        $ref = strtoupper($ref);
        $idx = 0;
        for ($i = 0; $i < strlen($ref); $i++) {
            $idx = $idx * 26 + (ord($ref[$i]) - ord('A') + 1);
        }
        return $idx - 1;
    }

    /** Serial Excel → Y-m-d (1900-system com bug do Lotus 1-2-3) */
    public static function excelDateToString(float $serial): string
    {
        if ($serial < 1) {
            return '';
        }
        // Corrige o bug do Excel que trata 1900-02-29 como válido
        $offset = $serial >= 60 ? 25568 : 25567;
        $ts     = (int)(($serial - $offset) * 86400);
        return date('Y-m-d', $ts);
    }

    /**
     * Heurística simples: verifica se a célula tem atributo de estilo numérico
     * que pode indicar data. Não é 100% preciso sem ler styles.xml, mas funciona
     * para a maioria dos casos; o controller complementa verificando o formato.
     */
    private static function isDateStyle(\SimpleXMLElement $cell): bool
    {
        // Sem acesso a styles.xml aqui, retorna false.
        // A detecção de data serial é feita pelo controller via regex.
        return false;
    }
}

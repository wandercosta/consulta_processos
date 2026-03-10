<?php

/**
 * Leitor simples de arquivo .env — sem dependências externas.
 * Compatible com PHP 7.4+.
 *
 * Ordem de prioridade para Env::get():
 *   1. Variável de ambiente do servidor (Apache SetEnv / sistema)
 *   2. Arquivo .env carregado via Env::load()
 *   3. Valor padrão informado pelo chamador
 */
class Env
{
    /** @var array<string,string> */
    private static array $data = [];
    private static bool  $loaded = false;

    /**
     * Carrega o arquivo .env indicado.
     * Seguro para chamadas múltiplas — só executa uma vez por processo.
     */
    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }

        self::$loaded = true; // marca antes de qualquer retorno

        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Ignora comentários e linhas sem '='
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }

            // Divide apenas no primeiro '=' (senhas podem conter '=')
            $pos   = strpos($line, '=');
            $key   = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            // Remove aspas duplas ou simples ao redor do valor
            if (
                strlen($value) >= 2
                && (
                    ($value[0] === '"'  && substr($value, -1) === '"')
                    || ($value[0] === "'" && substr($value, -1) === "'")
                )
            ) {
                $value = substr($value, 1, -1);
            }

            // Ignora chaves vazias
            if ($key === '') {
                continue;
            }

            self::$data[$key] = $value;
        }
    }

    /**
     * Retorna o valor de uma variável de ambiente.
     *
     * Prioridade: variável de servidor/sistema > .env > $default
     */
    public static function get(string $key, string $default = ''): string
    {
        // Variáveis definidas no servidor (Apache SetEnv, Docker, etc.) têm prioridade
        $serverVal = getenv($key);
        if ($serverVal !== false && $serverVal !== '') {
            return $serverVal;
        }

        return self::$data[$key] ?? $default;
    }
}

<?php

class ConfiguracaoModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Retorna todas as configurações como array chave => valor */
    public function getAll(): array
    {
        try {
            $rows = $this->db->query("SELECT chave, valor FROM configuracoes ORDER BY chave")->fetchAll(PDO::FETCH_ASSOC);
            $config = [];
            foreach ($rows as $row) {
                $config[$row['chave']] = $row['valor'];
            }
            return $config;
        } catch (\Exception $e) {
            return [];
        }
    }

    /** Retorna o valor de uma chave com fallback */
    public function get(string $chave, string $default = ''): string
    {
        try {
            $stmt = $this->db->prepare("SELECT valor FROM configuracoes WHERE chave = ?");
            $stmt->execute([$chave]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['valor'] : $default;
        } catch (\Exception $e) {
            return $default;
        }
    }

    /** Atualiza (ou insere) o valor de uma chave */
    public function set(string $chave, string $valor): void
    {
        $this->db->prepare("
            INSERT INTO configuracoes (chave, valor) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE valor = VALUES(valor)
        ")->execute([$chave, $valor]);
    }
}

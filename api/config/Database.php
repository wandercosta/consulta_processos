<?php

class Database {

    private string $host = "localhost";
    private string $db   = "consulta_processos";
    private string $user = "root";
    private string $pass = "";

    public function connect(): PDO
    {
        try {
            $pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->db};charset=utf8",
                $this->user,
                $this->pass
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            http_response_code(500);
            exit(json_encode([
                "erro"      => "Falha na conexão com o banco de dados",
                "mensagem"  => $e->getMessage(),
            ]));
        }
    }
}

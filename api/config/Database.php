<?php

class Database
{
    public function connect(): PDO
    {
        $host    = Env::get('DB_HOST',    'localhost');
        $db      = Env::get('DB_NAME',    'consulta_processos');
        $user    = Env::get('DB_USER',    'root');
        $pass    = Env::get('DB_PASS',    '');
        $charset = Env::get('DB_CHARSET', 'utf8');

        try {
            $pdo = new PDO(
                "mysql:host={$host};dbname={$db};charset={$charset}",
                $user,
                $pass
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            http_response_code(500);
            exit(json_encode([
                "erro"     => "Falha na conexão com o banco de dados",
                "mensagem" => $e->getMessage(),
            ]));
        }
    }
}

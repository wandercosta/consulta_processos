<?php

class AuthController
{
    public function login(): void
    {
        if (!empty($_SESSION['painel_logado'])) {
            header("Location: " . PAINEL_URL . "?page=dashboard");
            exit;
        }

        $erro = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (($_POST['senha'] ?? '') === PAINEL_SENHA) {
                $_SESSION['painel_logado'] = true;
                header("Location: " . PAINEL_URL . "?page=dashboard");
                exit;
            }
            $erro = 'Senha incorreta.';
        }

        include PAINEL_ROOT . '/Views/auth/login.php';
    }

    public function logout(): void
    {
        session_destroy();
        header("Location: " . PAINEL_URL . "?page=login");
        exit;
    }
}

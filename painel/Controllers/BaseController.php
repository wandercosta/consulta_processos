<?php

abstract class BaseController
{
    protected function render(string $view, array $data = []): void
    {
        extract($data);
        include PAINEL_ROOT . '/Views/layout/header.php';
        include PAINEL_ROOT . '/Views/' . $view . '.php';
        include PAINEL_ROOT . '/Views/layout/footer.php';
    }

    protected function redirect(string $page, array $params = []): void
    {
        $url = PAINEL_URL . '?page=' . $page;
        if (!empty($params)) {
            $url .= '&' . http_build_query($params);
        }
        header("Location: {$url}");
        exit;
    }
}

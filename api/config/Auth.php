<?php

const API_TOKEN = "CLAUDE_AUTOMACAO_123";

// Endpoints que não exigem token (servem arquivos ao browser)
const ENDPOINTS_PUBLICOS = ['download_arquivo', 'download_arquivo_id'];

function validarToken(): void
{
    $endpoint = $_GET['endpoint'] ?? '';

    if (in_array($endpoint, ENDPOINTS_PUBLICOS, true)) {
        return;
    }

    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if ($authHeader === '') {
        http_response_code(401);
        exit(json_encode(["erro" => "Token não enviado"]));
    }

    $token = str_replace("Bearer ", "", $authHeader);

    if ($token !== API_TOKEN) {
        http_response_code(403);
        exit(json_encode(["erro" => "Token inválido"]));
    }
}

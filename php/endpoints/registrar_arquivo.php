<?php

$db = (new Database())->connect();

$data = json_decode(file_get_contents("php://input"), true);

$id_processo     = $data['id_processo']     ?? null;
$nome_arquivo    = $data['nome_arquivo']    ?? null;
$caminho_arquivo = $data['caminho_arquivo'] ?? null;
$formato         = $data['formato']         ?? null;
$tamanho_bytes   = $data['tamanho_bytes']   ?? null;
$texto_doc       = $data['texto_doc']       ?? null;
$indice          = $data['indice']          ?? 1;
$download_ok     = $data['download_ok']     ?? 1;

if (!$id_processo || !$nome_arquivo) {
    http_response_code(400);
    echo json_encode(["erro" => "id_processo e nome_arquivo são obrigatórios"]);
    exit;
}

$stmt = $db->prepare("
    INSERT INTO processos_arquivos
        (id_processo, nome_arquivo, caminho_arquivo, formato, tamanho_bytes, texto_doc, indice, download_ok)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $id_processo,
    $nome_arquivo,
    $caminho_arquivo,
    $formato ? strtoupper($formato) : null,
    $tamanho_bytes,
    $texto_doc,
    (int)$indice,
    $download_ok ? 1 : 0,
]);

echo json_encode([
    "status" => "arquivo registrado",
    "id"     => $db->lastInsertId(),
]);

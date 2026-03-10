<?php

$db = (new Database())->connect();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if(!$id){
    http_response_code(400);
    echo json_encode(["erro" => "id do arquivo é obrigatório"]);
    exit;
}

$stmt = $db->prepare("
    SELECT a.caminho_arquivo, a.nome_arquivo, a.formato
    FROM processos_arquivos a
    WHERE a.id = ?
");
$stmt->execute([$id]);
$arquivo = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$arquivo){
    http_response_code(404);
    echo json_encode(["erro" => "Arquivo não encontrado"]);
    exit;
}

if(empty($arquivo['caminho_arquivo'])){
    http_response_code(404);
    echo json_encode(["erro" => "Caminho do arquivo não registrado"]);
    exit;
}

// Normaliza separadores (Python salva com \ no Windows)
$caminho = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $arquivo['caminho_arquivo']);

if(!file_exists($caminho)){
    http_response_code(404);
    echo json_encode(["erro" => "Arquivo não encontrado no disco"]);
    exit;
}

while(ob_get_level()) ob_end_clean();

$extensao = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));
$tipos = [
    'pdf'  => 'application/pdf',
    'html' => 'text/html',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'doc'  => 'application/msword',
    'zip'  => 'application/zip',
];
$mime = $tipos[$extensao] ?? 'application/octet-stream';

$nomeDownload = $arquivo['nome_arquivo'] ?: ('arquivo_' . $id . '.' . $extensao);

header("Content-Type: {$mime}");
header("Content-Disposition: attachment; filename=\"{$nomeDownload}\"");
header("Content-Length: " . filesize($caminho));
header("Cache-Control: no-cache");
readfile($caminho);
exit;

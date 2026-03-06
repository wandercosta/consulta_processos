<?php

$db = (new Database())->connect();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if(!$id){
    http_response_code(400);
    echo json_encode(["erro" => "id do processo é obrigatório"]);
    exit;
}

$stmt = $db->prepare("SELECT numero_processo, caminho_arquivo FROM processos WHERE id = ?");
$stmt->execute([$id]);
$processo = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$processo){
    http_response_code(404);
    echo json_encode(["erro" => "Processo não encontrado"]);
    exit;
}

if(empty($processo['caminho_arquivo'])){
    http_response_code(404);
    echo json_encode(["erro" => "Este processo não possui arquivo"]);
    exit;
}

$caminho = $processo['caminho_arquivo'];

if(!file_exists($caminho)){
    http_response_code(404);
    echo json_encode(["erro" => "Arquivo não encontrado no disco"]);
    exit;
}

// Limpa qualquer saída anterior e desativa o header JSON do index.php
while(ob_get_level()) ob_end_clean();

$extensao = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));
$tipos = [
    'pdf'  => 'application/pdf',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'doc'  => 'application/msword',
    'zip'  => 'application/zip',
];
$mime = $tipos[$extensao] ?? 'application/octet-stream';

$nomeArquivo = 'processo_' . preg_replace('/[^a-z0-9]/i', '_', $processo['numero_processo']) . '.' . $extensao;

header("Content-Type: {$mime}");
header("Content-Disposition: attachment; filename=\"{$nomeArquivo}\"");
header("Content-Length: " . filesize($caminho));
header("Cache-Control: no-cache");
readfile($caminho);
exit;

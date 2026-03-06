<?php

$db = (new Database())->connect();

$data = json_decode(file_get_contents("php://input"), true);

if(empty($data['numero_processo'])){
    http_response_code(400);
    echo json_encode(["erro" => "numero_processo é obrigatório"]);
    exit;
}

$numero = trim($data['numero_processo']);

// Verifica duplicata
$check = $db->prepare("SELECT id FROM processos WHERE numero_processo = ?");
$check->execute([$numero]);
if($check->fetch()){
    http_response_code(409);
    echo json_encode(["erro" => "Processo já cadastrado"]);
    exit;
}

$sql = "INSERT INTO processos (numero_processo, status_consulta, criado_em) VALUES (?, 'PENDENTE', NOW())";
$stmt = $db->prepare($sql);
$stmt->execute([$numero]);

echo json_encode([
    "status" => "processo cadastrado",
    "id"     => $db->lastInsertId()
]);

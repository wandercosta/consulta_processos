<?php

$db = (new Database())->connect();

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id_processo'];
$mensagem = $data['mensagem_erro'] ?? 'Erro não informado';

$sql = "
UPDATE processos
SET
status_consulta='ERRO',
mensagem_erro=?,
data_ultima_consulta=NOW()
WHERE id=?
";

$stmt = $db->prepare($sql);
$stmt->execute([$mensagem, $id]);

echo json_encode([
    "status" => "erro registrado"
]);
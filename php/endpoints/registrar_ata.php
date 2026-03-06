<?php

$db = (new Database())->connect();

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id_processo'];
$qtd = $data['qtd_atas'];
$caminho = $data['arquivo'];

$sql = "

UPDATE processos
SET
status_consulta='FINALIZADO',
possui_ata='S',
qtd_atas=?,
caminho_arquivo=?,
data_ultima_consulta=NOW()
WHERE id=?

";

$stmt = $db->prepare($sql);
$stmt->execute([$qtd,$caminho,$id]);

echo json_encode([
    "status"=>"ata registrada"
]);
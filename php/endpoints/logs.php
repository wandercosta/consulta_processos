<?php

$db = (new Database())->connect();

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id_processo'];
$msg = $data['mensagem'];
$status = $data['status'];

$sql = "

INSERT INTO processos_logs
(id_processo,mensagem,status)
VALUES (?,?,?)

";

$stmt = $db->prepare($sql);
$stmt->execute([$id,$msg,$status]);

echo json_encode([
    "status"=>"log salvo"
]);
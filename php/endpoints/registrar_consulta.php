<?php

$db = (new Database())->connect();

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id_processo'];

$sql = "

UPDATE processos
SET
status_consulta='CONSULTANDO',
data_ultima_consulta=NOW()
WHERE id=?

";

$stmt = $db->prepare($sql);
$stmt->execute([$id]);

echo json_encode([
    "status"=>"ok"
]);
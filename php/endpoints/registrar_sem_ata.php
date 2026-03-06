<?php

$db = (new Database())->connect();

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id_processo'];

$sql = "
UPDATE processos
SET
status_consulta='FINALIZADO',
possui_ata='N',
qtd_atas=0,
caminho_arquivo=NULL,
data_ultima_consulta=NOW(),
mensagem_erro=NULL
WHERE id=?
";

$stmt = $db->prepare($sql);
$stmt->execute([$id]);

echo json_encode([
    "status" => "processo finalizado sem ata"
]);
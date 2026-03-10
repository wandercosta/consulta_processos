<?php

$db = (new Database())->connect();

$data = json_decode(file_get_contents("php://input"), true);

$id      = $data['id_processo'];
$qtd     = $data['qtd_atas'];
$caminho = $data['arquivo'];

$stmt = $db->prepare("
    UPDATE processos
    SET
        status_consulta      = 'FINALIZADO COM ATA',
        possui_ata           = 'S',
        qtd_atas             = ?,
        caminho_arquivo      = ?,
        data_ultima_consulta = NOW(),
        mensagem_erro        = NULL
    WHERE id = ?
");
$stmt->execute([$qtd, $caminho, $id]);

echo json_encode([
    "status" => "ata registrada"
]);

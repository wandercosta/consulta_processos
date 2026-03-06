<?php

$db = (new Database())->connect();

$sql = "

SELECT
id,
numero_processo,
'TJMG' AS tribunal
FROM processos
WHERE status_consulta = 'PENDENTE'
ORDER BY criado_em
LIMIT 10

";

$stmt = $db->query($sql);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
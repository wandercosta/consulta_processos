<?php

$db = (new Database())->connect();

$sql = "
    SELECT
        id,
        id AS id_processo,
        numero_processo,
        status_consulta,
        'TJMG' AS tribunal
    FROM processos
    WHERE status_consulta = 'PENDENTE'
       OR (
           status_consulta       = 'FINALIZADO SEM ATA'
           AND data_ultima_consulta < NOW() - INTERVAL 10 MINUTE
       )
    ORDER BY
        CASE status_consulta WHEN 'PENDENTE' THEN 0 ELSE 1 END ASC,
        criado_em ASC
    LIMIT 10
";

$stmt = $db->query($sql);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

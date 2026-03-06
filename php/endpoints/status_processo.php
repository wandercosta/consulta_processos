<?php

$db = (new Database())->connect();

$id = $_GET['id'];

$sql = "

SELECT *
FROM processos
WHERE id=?

";

$stmt = $db->prepare($sql);
$stmt->execute([$id]);

echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
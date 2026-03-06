<?php

$db = (new Database())->connect();

$status  = $_GET['status']  ?? '';
$search  = $_GET['search']  ?? '';
$possui_ata = $_GET['possui_ata'] ?? '';
$data_de = $_GET['data_de'] ?? '';
$data_ate = $_GET['data_ate'] ?? '';
$pagina  = max(1, (int)($_GET['pagina'] ?? 1));
$limite  = min(100, max(1, (int)($_GET['limite'] ?? 20)));
$offset  = ($pagina - 1) * $limite;

$where  = [];
$params = [];

if($status !== ''){
    $where[] = "status_consulta = ?";
    $params[] = $status;
}

if($search !== ''){
    $where[] = "numero_processo LIKE ?";
    $params[] = "%{$search}%";
}

if($possui_ata !== ''){
    $where[] = "possui_ata = ?";
    $params[] = $possui_ata;
}

if($data_de !== ''){
    $where[] = "DATE(criado_em) >= ?";
    $params[] = $data_de;
}

if($data_ate !== ''){
    $where[] = "DATE(criado_em) <= ?";
    $params[] = $data_ate;
}

$whereSql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

// Total para paginação
$countParams = $params;
$countStmt = $db->prepare("SELECT COUNT(*) FROM processos {$whereSql}");
$countStmt->execute($countParams);
$total = (int)$countStmt->fetchColumn();

// Busca paginada
$sql = "SELECT id, numero_processo, status_consulta, possui_ata, qtd_atas,
               caminho_arquivo, data_ultima_consulta, criado_em, mensagem_erro
        FROM processos {$whereSql}
        ORDER BY criado_em DESC
        LIMIT {$limite} OFFSET {$offset}";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$processos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "total"    => $total,
    "pagina"   => $pagina,
    "limite"   => $limite,
    "paginas"  => (int)ceil($total / $limite),
    "dados"    => $processos
]);

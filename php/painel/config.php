<?php

define('PAINEL_SENHA', 'admin123'); // Altere para uma senha segura
define('API_TOKEN', 'CLAUDE_AUTOMACAO_123');
define('API_URL', 'http://localhost/processos_api/');

session_start();

function requerLogin(){
    if(empty($_SESSION['painel_logado'])){
        header("Location: login.php");
        exit;
    }
}

function db(){
    static $pdo = null;
    if($pdo === null){
        require_once __DIR__ . '/../config/database.php';
        $pdo = (new Database())->connect();
    }
    return $pdo;
}

function statusBadge(?string $status): string {
    if($status === null) return '<span class="badge bg-secondary">—</span>';
    $map = [
        'PENDENTE'           => ['warning',   'bi-hourglass-split'],
        'CONSULTANDO'        => ['info',      'bi-arrow-repeat'],
        'FINALIZADO COM ATA' => ['success',   'bi-check-circle-fill'],
        'FINALIZADO SEM ATA' => ['secondary', 'bi-clock-history'],
        'FINALIZADO'         => ['success',   'bi-check-circle'],   // retrocompatibilidade
        'ERRO'               => ['danger',    'bi-x-circle'],
    ];
    [$cor, $icone] = $map[$status] ?? ['secondary', 'bi-question-circle'];
    return "<span class=\"badge bg-{$cor}\"><i class=\"bi {$icone} me-1\"></i>{$status}</span>";
}

function ataBadge(?string $possui): string {
    if($possui === 'S') return '<span class="badge bg-success"><i class="bi bi-check me-1"></i>Sim</span>';
    if($possui === 'N') return '<span class="badge bg-secondary"><i class="bi bi-x me-1"></i>Não</span>';
    return '<span class="badge bg-light text-dark">—</span>';
}

function formatData(?string $dt): string {
    if(!$dt) return '—';
    return date('d/m/Y H:i', strtotime($dt));
}

function formatBytes(int $bytes): string {
    if($bytes <= 0) return '—';
    if($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
    if($bytes >= 1024)    return number_format($bytes / 1024, 0) . ' KB';
    return $bytes . ' B';
}

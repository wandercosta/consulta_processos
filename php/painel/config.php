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

function statusBadge(string $status): string {
    $map = [
        'PENDENTE'    => 'warning',
        'CONSULTANDO' => 'info',
        'FINALIZADO'  => 'success',
        'ERRO'        => 'danger',
    ];
    $cor = $map[$status] ?? 'secondary';
    return "<span class=\"badge bg-{$cor}\">{$status}</span>";
}

function ataBadge(?string $possui): string {
    if($possui === 'S') return '<span class="badge bg-success">Sim</span>';
    if($possui === 'N') return '<span class="badge bg-secondary">Não</span>';
    return '<span class="badge bg-light text-dark">—</span>';
}

function formatData(?string $dt): string {
    if(!$dt) return '—';
    return date('d/m/Y H:i', strtotime($dt));
}

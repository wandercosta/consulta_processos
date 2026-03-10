<?php

header("Content-Type: application/json");

require_once "config/database.php";
require_once "config/auth.php";

validarToken();

$endpoint = $_GET['endpoint'] ?? '';

switch($endpoint){

    case "processos_pendentes":
        require "endpoints/processos_pendentes.php";
        break;

    case "registrar_consulta":
        require "endpoints/registrar_consulta.php";
        break;

    case "registrar_ata":
        require "endpoints/registrar_ata.php";
        break;

    case "status_processo":
        require "endpoints/status_processo.php";
        break;

    case "logs":
        require "endpoints/logs.php";
        break;

    case "registrar_sem_ata":
        require "endpoints/registrar_sem_ata.php";
        break;

    case "registrar_erro":
        require "endpoints/registrar_erro.php";
        break;

    case "cadastrar_processo":
        require "endpoints/cadastrar_processo.php";
        break;

    case "listar_processos":
        require "endpoints/listar_processos.php";
        break;

    case "download_arquivo":
        require "endpoints/download_arquivo.php";
        break;

    case "registrar_arquivo":
        require "endpoints/registrar_arquivo.php";
        break;

    case "download_arquivo_id":
        require "endpoints/download_arquivo_id.php";
        break;

    default:
        echo json_encode([
            "erro" => "Endpoint não encontrado"
        ]);

}
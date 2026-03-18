<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Automação de Processos</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    body {
        min-height: 100vh;
        background: #0f172a;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Segoe UI', sans-serif;
    }

    .home-wrap {
        width: 100%;
        max-width: 560px;
        padding: 2rem 1rem;
    }

    .brand {
        text-align: center;
        margin-bottom: 2.5rem;
    }

    .brand-icon {
        font-size: 3rem;
        color: #6366f1;
    }

    .brand h1 {
        color: #f1f5f9;
        font-size: 1.6rem;
        font-weight: 700;
        margin: .5rem 0 .25rem;
    }

    .brand p {
        color: #94a3b8;
        font-size: .9rem;
        margin: 0;
    }

    .card-menu {
        background: #1e293b;
        border: 1px solid #334155;
        border-radius: 12px;
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 1.1rem;
        color: #f1f5f9;
        text-decoration: none;
        transition: background .18s, border-color .18s, transform .15s;
        margin-bottom: .75rem;
    }

    .card-menu:hover {
        background: #273549;
        border-color: #6366f1;
        color: #f1f5f9;
        transform: translateY(-2px);
    }

    .card-menu .icon-wrap {
        width: 46px;
        height: 46px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }

    .icon-painel  { background: #312e81; color: #a5b4fc; }
    .icon-api     { background: #064e3b; color: #6ee7b7; }
    .icon-docs    { background: #1e3a5f; color: #93c5fd; }
    .icon-python  { background: #3b2a00; color: #fcd34d; }

    .card-menu .info h5 {
        font-size: .95rem;
        font-weight: 600;
        margin: 0 0 .15rem;
    }

    .card-menu .info span {
        font-size: .8rem;
        color: #94a3b8;
    }

    .card-menu .arrow {
        margin-left: auto;
        color: #475569;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .card-menu:hover .arrow {
        color: #6366f1;
    }

    .footer-note {
        text-align: center;
        color: #475569;
        font-size: .78rem;
        margin-top: 1.75rem;
    }
</style>
</head>
<body>

<div class="home-wrap">

    <div class="brand">
        <div class="brand-icon"><i class="bi bi-folder2-open"></i></div>
        <h1>Automação de Processos</h1>
        <p>Consulta e gestão de processos jurídicos</p>
    </div>

    <a href="/painel/" class="card-menu">
        <div class="icon-wrap icon-painel">
            <i class="bi bi-speedometer2"></i>
        </div>
        <div class="info">
            <h5>Painel Administrativo</h5>
            <span>Dashboard, processos, ATAs e configurações</span>
        </div>
        <i class="bi bi-chevron-right arrow"></i>
    </a>

    <a href="/api/" class="card-menu">
        <div class="icon-wrap icon-api">
            <i class="bi bi-plug"></i>
        </div>
        <div class="info">
            <h5>API REST</h5>
            <span>Endpoints para integração com sistemas externos</span>
        </div>
        <i class="bi bi-chevron-right arrow"></i>
    </a>

    <a href="/painel/?page=docs" class="card-menu">
        <div class="icon-wrap icon-docs">
            <i class="bi bi-book"></i>
        </div>
        <div class="info">
            <h5>Documentação</h5>
            <span>Guia de uso da API e do robô de automação</span>
        </div>
        <i class="bi bi-chevron-right arrow"></i>
    </a>

    <a href="/painel/?page=robot" class="card-menu">
        <div class="icon-wrap icon-python">
            <i class="bi bi-robot"></i>
        </div>
        <div class="info">
            <h5>Painel do Robô (Python)</h5>
            <span>Ativar/desativar daemon, status, heartbeat e PID</span>
        </div>
        <i class="bi bi-chevron-right arrow"></i>
    </a>

    <p class="footer-note">processos.auradevcode.com.br</p>

</div>

</body>
</html>

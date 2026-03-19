<?php
// Variáveis injetadas pelo router: $paginaAtual, $tituloPagina
$apiBase = rtrim(str_replace('/index.php', '', API_DOWNLOAD_URL), '/');
?>

<!-- Tabs de navegação -->
<ul class="nav nav-tabs mb-4" id="docsTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#visao-geral">
            <i class="bi bi-diagram-3 me-1"></i>Visão Geral
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#fluxo">
            <i class="bi bi-arrow-repeat me-1"></i>Fluxo de Operação
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#webhook">
            <i class="bi bi-send me-1"></i>Webhook
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#api">
            <i class="bi bi-code-slash me-1"></i>API REST
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#banco">
            <i class="bi bi-database me-1"></i>Banco de Dados
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#classificacao">
            <i class="bi bi-tags me-1"></i>Classificação
        </button>
    </li>
</ul>

<div class="tab-content">

    <!-- ── VISÃO GERAL ──────────────────────────────────────────────────────── -->
    <div class="tab-pane fade show active" id="visao-geral">
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm" style="border-radius:12px">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-2"></i>O que é o sistema?</h5>
                        <p>O <strong>processos_api</strong> é um sistema de automação para consulta e download de Atas de Audiência em portais judiciais brasileiros. Ele combina:</p>
                        <ul>
                            <li><strong>API REST (PHP)</strong> — gerencia o banco de dados de processos, recebe resultados do robô e serve arquivos para download.</li>
                            <li><strong>Painel Web (PHP)</strong> — interface administrativa para cadastrar processos, acompanhar o status e visualizar as ATAs baixadas.</li>
                            <li><strong>Robô Python</strong> — daemon que usa Selenium/Chrome para acessar os portais judiciais, localizar as ATAs e fazer o download automaticamente.</li>
                        </ul>

                        <h6 class="fw-bold mt-4 mb-2">Arquitetura</h6>
                        <pre class="bg-light rounded p-3 small">processos_api/
├── api/          → REST API (PHP + PDO, DDD pragmático)
│   ├── config/   → Database, Auth, Env
│   ├── Domain/   → Entidades e interfaces de repositório
│   ├── Infrastructure/ → Implementações PDO
│   └── Http/Controllers/ → Controladores dos endpoints
├── painel/       → Interface administrativa (PHP MVC)
│   ├── Controllers/
│   ├── Models/
│   ├── Views/
│   └── migrations/ → Migrações incrementais do banco
└── python/       → Robô de automação (Selenium)
    ├── core/     → API client, downloader, driver
    ├── tribunais/ → Scrapers por tribunal
    └── models/   → Entidade Documento</pre>

                        <h6 class="fw-bold mt-4 mb-2">Tecnologias</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-primary">PHP 8+ (puro)</span>
                            <span class="badge bg-secondary">MySQL / PDO</span>
                            <span class="badge bg-success">Python 3.11+</span>
                            <span class="badge bg-warning text-dark">Selenium + ChromeDriver</span>
                            <span class="badge bg-info text-dark">Bootstrap 5</span>
                            <span class="badge bg-dark">Bootstrap Icons</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm" style="border-radius:12px">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-link-45deg text-primary me-1"></i>Acesso Rápido</h6>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <a href="<?= PAINEL_URL ?>?page=cadastrar" class="text-decoration-none">
                                    <i class="bi bi-plus-circle me-1 text-primary"></i>Cadastrar processo
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="<?= PAINEL_URL ?>?page=processos" class="text-decoration-none">
                                    <i class="bi bi-journal-text me-1 text-primary"></i>Listar processos
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="<?= PAINEL_URL ?>?page=arquivos" class="text-decoration-none">
                                    <i class="bi bi-file-earmark-arrow-down me-1 text-success"></i>Arquivos / ATAs
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="<?= PAINEL_URL ?>?page=robot" class="text-decoration-none">
                                    <i class="bi bi-robot me-1 text-warning"></i>Controle do Robô
                                </a>
                            </li>
                            <li>
                                <a href="<?= PAINEL_URL_BASE ?>migrations/" class="text-decoration-none" target="_blank">
                                    <i class="bi bi-database-gear me-1 text-secondary"></i>Runner de migrações
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-3" style="border-radius:12px">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-2"><i class="bi bi-shield-lock text-danger me-1"></i>Autenticação da API</h6>
                        <p class="small text-muted mb-2">Todos os endpoints (exceto download/visualização) exigem:</p>
                        <pre class="bg-light rounded p-2 small mb-0">Authorization: Bearer CLAUDE_AUTOMACAO_123</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── FLUXO DE OPERAÇÃO ───────────────────────────────────────────────── -->
    <div class="tab-pane fade" id="fluxo">
        <div class="card border-0 shadow-sm" style="border-radius:12px">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-arrow-repeat text-primary me-2"></i>Ciclo de vida de um processo</h5>

                <div class="row g-4">
                    <div class="col-md-6">
                        <h6 class="fw-bold text-muted text-uppercase small mb-3">1. Cadastro</h6>
                        <ol class="small">
                            <li class="mb-1">Usuário cadastra o processo pelo painel (ou via API).</li>
                            <li class="mb-1">Sistema detecta automaticamente o <strong>tipo</strong> pelo 1º dígito do número (MG: 5=PJE, 0/1=EPROC, 2=PROCON).</li>
                            <li class="mb-1">Processo entra com status <span class="badge bg-warning text-dark">PENDENTE</span>.</li>
                            <li>Se informada, a <strong>data do ato</strong> é registrada — o robô só processa após essa data.</li>
                        </ol>
                    </div>

                    <div class="col-md-6">
                        <h6 class="fw-bold text-muted text-uppercase small mb-3">2. Daemon Python</h6>
                        <ol class="small">
                            <li class="mb-1">A cada ciclo (padrão: 20s), o daemon chama <code>GET /processos_pendentes</code>.</li>
                            <li class="mb-1">Verifica se o <code>tipo_sistema</code> tem scraper disponível; se não: marca <span class="badge bg-dark">NÃO COMPATÍVEL</span> e encerra.</li>
                            <li class="mb-1">Abre o Chrome, acessa o portal judicial e busca as ATAs.</li>
                            <li>Filtra documentos com <code>data_documento &lt; data_ato</code> (se configurada).</li>
                        </ol>
                    </div>

                    <div class="col-md-6">
                        <h6 class="fw-bold text-muted text-uppercase small mb-3">3. Download e registro</h6>
                        <ol class="small">
                            <li class="mb-1">Para cada ATA encontrada: baixa o arquivo (PDF ou HTML).</li>
                            <li class="mb-1">Chama <code>POST /registrar_arquivo</code> com metadados e texto extraído.</li>
                            <li class="mb-1">Envia o binário via <code>POST /upload_arquivo</code> (multipart).</li>
                            <li>Ao final: chama <code>POST /registrar_ata</code> → status <span class="badge bg-success">FINALIZADO COM ATA</span>.</li>
                        </ol>
                    </div>

                    <div class="col-md-6">
                        <h6 class="fw-bold text-muted text-uppercase small mb-3">4. Sem ATA / Erro</h6>
                        <ol class="small">
                            <li class="mb-1">Nenhuma ATA encontrada → <code>POST /registrar_sem_ata</code> → <span class="badge bg-secondary">FINALIZADO SEM ATA</span>.</li>
                            <li class="mb-1">Processos com este status são <strong>reprocessados automaticamente</strong> após 10 minutos.</li>
                            <li class="mb-1">Erro inesperado → <code>POST /registrar_erro</code> → <span class="badge bg-danger">ERRO</span>.</li>
                            <li>Usuário pode <strong>cancelar</strong> ou <strong>recolocar na fila</strong> manualmente pelo painel.</li>
                        </ol>
                    </div>
                </div>

                <hr class="my-4">
                <h6 class="fw-bold mb-3">Status possíveis</h6>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>PENDENTE</span>
                    <span class="badge bg-info text-dark"><i class="bi bi-arrow-repeat me-1"></i>CONSULTANDO</span>
                    <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>FINALIZADO COM ATA</span>
                    <span class="badge bg-secondary"><i class="bi bi-clock-history me-1"></i>FINALIZADO SEM ATA</span>
                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>ERRO</span>
                    <span class="badge bg-dark"><i class="bi bi-slash-circle me-1"></i>NÃO COMPATÍVEL</span>
                    <span class="badge bg-secondary"><i class="bi bi-pause-circle me-1"></i>CANCELADO</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ── WEBHOOK ────────────────────────────────────────────────────────── -->
    <div class="tab-pane fade" id="webhook">
        <div class="row g-3">

            <!-- Visão geral + configuração -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm" style="border-radius:12px">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-send text-primary me-2"></i>O que é o webhook?</h5>
                        <p class="small text-muted">
                            Após cada finalização de consulta, o sistema envia automaticamente um <strong>HTTP POST</strong> para a URL configurada,
                            entregando o resultado do processo em JSON. Isso permite que sistemas externos (ERPs, CRMs, plataformas jurídicas)
                            sejam notificados em tempo real sem polling.
                        </p>

                        <h6 class="fw-bold mt-4 mb-2">Configuração</h6>
                        <p class="small text-muted mb-2">Acesse <a href="<?= PAINEL_URL ?>?page=webhook">Painel → Webhooks</a> e preencha:</p>
                        <table class="table table-sm small mb-0">
                            <tbody>
                                <tr>
                                    <td class="fw-semibold ps-0" style="width:90px">URL</td>
                                    <td>Endpoint HTTPS do seu sistema que receberá os POSTs</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold ps-0">Secret</td>
                                    <td>Chave enviada no header <code>X-Webhook-Secret</code> para validar autenticidade (opcional)</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold ps-0">Ativo</td>
                                    <td>Liga/desliga todos os disparos sem apagar a configuração</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-3" style="border-radius:12px">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-bell text-warning me-1"></i>Quando o webhook é disparado?</h6>
                        <p class="small text-muted mb-2">Um POST é enviado sempre que o robô <strong>finaliza</strong> a consulta de um processo — independente do resultado:</p>
                        <ul class="list-unstyled small mb-0">
                            <li class="mb-2">
                                <span class="badge bg-success me-2">FINALIZADO COM ATA</span>
                                Ata(s) encontrada(s) e baixada(s)
                            </li>
                            <li class="mb-2">
                                <span class="badge bg-secondary me-2">FINALIZADO SEM ATA</span>
                                Consultado, nenhuma ata disponível
                            </li>
                            <li class="mb-2">
                                <span class="badge bg-danger me-2">ERRO</span>
                                Falha durante a execução do robô
                            </li>
                            <li>
                                <span class="badge bg-dark me-2">NÃO COMPATÍVEL</span>
                                Tipo de sistema não suportado
                            </li>
                        </ul>
                        <p class="small text-muted mt-3 mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            O status <strong>CONSULTANDO</strong> não dispara webhook — ele indica apenas que o robô iniciou o trabalho.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Fluxo + payload -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-diagram-2 text-primary me-1"></i>Fluxo de disparo</h6>
                        <pre class="bg-light rounded p-3 small mb-0" style="font-size:.8rem; line-height:1.7">Robô Python
    │
    ├─ POST /registrar_ata         ──┐
    ├─ POST /registrar_sem_ata     ──┤
    ├─ POST /registrar_erro        ──┤──► API PHP (ProcessoController)
    └─ POST /registrar_nao_compat  ──┘         │
                                               │ 1. Atualiza status no BD
                                               │ 2. WebhookService::disparar(id)
                                               │    ├─ Lê webhook_config (url, ativo, secret)
                                               │    ├─ Ativo = false? → encerra silenciosamente
                                               │    ├─ Monta payload JSON com dados do processo
                                               │    │  + lista de arquivos com URLs públicas
                                               │    ├─ Envia HTTP POST (timeout: 10s)
                                               │    └─ Grava resultado em webhook_logs
                                               │       (status HTTP, resposta, sucesso S/N)
                                               │
                                               └─ Responde ao robô: { "status": "ok" }</pre>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-braces text-success me-1"></i>Payload JSON enviado</h6>
                        <p class="small text-muted mb-2">Headers: <code>Content-Type: application/json</code> · <code>X-Webhook-Event: &lt;STATUS&gt;</code> · <code>X-Webhook-Secret: ***</code> (se configurado)</p>
                        <pre class="bg-light rounded p-3 small mb-0" style="font-size:.78rem"><?= htmlspecialchars(json_encode([
    'evento'          => 'FINALIZADO COM ATA',
    'id_integracao'   => 'ORD-2025-001',
    'numero_processo' => '5003854-46.2025.8.13.0407',
    'status'          => 'FINALIZADO COM ATA',
    'tribunal'        => 'MG',
    'tipo_sistema'    => 'PJE',
    'qtd_atas'        => 1,
    'data_consulta'   => '2025-03-12 10:30:00',
    'arquivos'        => [[
        'id'            => 42,
        'nome'          => 'ata_audiencia.pdf',
        'formato'       => 'PDF',
        'tamanho_bytes' => 102400,
        'url'           => 'https://servidor.com/api/?endpoint=download_arquivo_id&id=42',
    ]],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                        <table class="table table-sm small mt-3 mb-0">
                            <thead><tr><th>Campo</th><th>Descrição</th></tr></thead>
                            <tbody>
                                <tr><td class="font-monospace">evento</td><td>Mesmo valor de <code>status</code> — facilita filtros no receptor</td></tr>
                                <tr><td class="font-monospace">id_integracao</td><td>Valor do campo <code>cod_api</code> informado no cadastro (pode ser <code>null</code>)</td></tr>
                                <tr><td class="font-monospace">numero_processo</td><td>Número CNJ do processo</td></tr>
                                <tr><td class="font-monospace">tribunal</td><td>UF do estado (ex: <code>MG</code>)</td></tr>
                                <tr><td class="font-monospace">qtd_atas</td><td>Quantidade de arquivos baixados (<code>0</code> para SEM ATA / ERRO)</td></tr>
                                <tr><td class="font-monospace">arquivos[].url</td><td>URL pública para download direto do arquivo (sem autenticação)</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card border-0 shadow-sm" style="border-radius:12px">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-arrow-repeat text-info me-1"></i>Reenvio e histórico</h6>
                        <p class="small text-muted mb-2">
                            Cada tentativa de envio é gravada em <code>webhook_logs</code> com payload, status HTTP e resposta.
                            Em caso de falha (timeout, erro 4xx/5xx), o webhook <strong>não é reenviado automaticamente</strong> —
                            acesse <a href="<?= PAINEL_URL ?>?page=webhook">Painel → Webhooks</a> para reenviar manualmente.
                        </p>
                        <div class="bg-light rounded p-3 small">
                            <strong>Dica de validação no receptor:</strong><br>
                            Verifique o header <code>X-Webhook-Secret</code> antes de processar o payload.
                            Responda com <code>HTTP 200</code> para confirmar o recebimento —
                            qualquer código fora de <code>2xx</code> é registrado como falha no histórico.
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ── API REST ────────────────────────────────────────────────────────── -->
    <div class="tab-pane fade" id="api">
        <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
            <div class="card-body p-3">
                <span class="small text-muted">URL base: </span>
                <code><?= $apiBase ?>/?endpoint=</code>
                <span class="badge bg-danger ms-2">Bearer CLAUDE_AUTOMACAO_123</span>
                <span class="badge bg-success ms-1">Content-Type: application/json</span>
            </div>
        </div>

        <div class="accordion" id="apiAccordion">

            <?php
            $endpoints = [
                [
                    'id' => 'pendentes',
                    'method' => 'GET', 'color' => 'primary',
                    'endpoint' => 'processos_pendentes',
                    'titulo' => 'Buscar processos pendentes',
                    'descricao' => 'Retorna até 10 processos com status PENDENTE ou FINALIZADO SEM ATA (expirados ≥10 min). Filtra automaticamente processos com data_ato > hoje.',
                    'payload' => null,
                    'resposta' => '[
  {
    "id": 42,
    "id_processo": 42,
    "numero_processo": "5003854-46.2025.8.13.0407",
    "tribunal": "MG",
    "tipo_sistema": "PJE",
    "status_consulta": "PENDENTE",
    "data_ato": "2025-03-01"
  }
]',
                ],
                [
                    'id' => 'registrar-consulta',
                    'method' => 'POST', 'color' => 'warning',
                    'endpoint' => 'registrar_consulta',
                    'titulo' => 'Registrar início de consulta',
                    'descricao' => 'Muda o status do processo para CONSULTANDO.',
                    'payload' => '{ "id_processo": 42 }',
                    'resposta' => '{ "status": "ok" }',
                ],
                [
                    'id' => 'registrar-ata',
                    'method' => 'POST', 'color' => 'warning',
                    'endpoint' => 'registrar_ata',
                    'titulo' => 'Registrar conclusão com ATA',
                    'descricao' => 'Muda status para FINALIZADO COM ATA.',
                    'payload' => '{ "id_processo": 42, "qtd_atas": 2, "arquivo": "ata_1.pdf | ata_2.pdf" }',
                    'resposta' => '{ "status": "ok" }',
                ],
                [
                    'id' => 'registrar-sem-ata',
                    'method' => 'POST', 'color' => 'warning',
                    'endpoint' => 'registrar_sem_ata',
                    'titulo' => 'Registrar conclusão sem ATA',
                    'descricao' => 'Muda status para FINALIZADO SEM ATA. Processo será reprocessado após 10 minutos.',
                    'payload' => '{ "id_processo": 42 }',
                    'resposta' => '{ "status": "ok" }',
                ],
                [
                    'id' => 'registrar-erro',
                    'method' => 'POST', 'color' => 'warning',
                    'endpoint' => 'registrar_erro',
                    'titulo' => 'Registrar erro',
                    'descricao' => 'Muda status para ERRO e salva a mensagem.',
                    'payload' => '{ "id_processo": 42, "mensagem_erro": "Timeout ao acessar o portal" }',
                    'resposta' => '{ "status": "ok" }',
                ],
                [
                    'id' => 'nao-compativel',
                    'method' => 'POST', 'color' => 'warning',
                    'endpoint' => 'registrar_nao_compativel',
                    'titulo' => 'Registrar NÃO COMPATÍVEL',
                    'descricao' => 'Muda status para NÃO COMPATÍVEL. Usado quando o tipo_sistema não tem scraper implementado. O processo não volta para a fila automaticamente.',
                    'payload' => '{ "id_processo": 42, "mensagem": "Sistema \'EPROC\' não suportado para MG." }',
                    'resposta' => '{ "status": "ok" }',
                ],
                [
                    'id' => 'cadastrar',
                    'method' => 'POST', 'color' => 'warning',
                    'endpoint' => 'cadastrar_processo',
                    'titulo' => 'Cadastrar processo',
                    'descricao' => 'Cadastra um novo processo. O tipo_sistema é inferido automaticamente. tribunal, data_ato e cod_api são opcionais. cod_api é seu identificador interno — retornado no webhook como id_integracao.',
                    'payload' => '{
  "numero_processo": "5003854-46.2025.8.13.0407",
  "tribunal": "MG",
  "data_ato": "2025-03-01",
  "cod_api": "ORD-2025-001"
}',
                    'resposta' => '{ "id": 43, "status": "ok" }',
                ],
                [
                    'id' => 'cadastrar-lote',
                    'method' => 'POST', 'color' => 'warning',
                    'endpoint' => 'cadastrar_processo',
                    'titulo' => 'Cadastro em lote (loop)',
                    'descricao' => 'Não há endpoint de lote — chame cadastrar_processo repetidamente para cada processo. Use cod_api para vincular cada processo ao seu sistema. Processos duplicados retornam erro 409.',
                    'payload' => '# Exemplo em Python
processos = [
    {"numero_processo": "5001111-11.2025.8.13.0001", "tribunal": "MG", "data_ato": "2025-03-01", "cod_api": "ORD-001"},
    {"numero_processo": "5002222-22.2025.8.13.0002", "tribunal": "MG", "data_ato": "2025-03-01", "cod_api": "ORD-002"},
]

for p in processos:
    r = requests.post(
        "https://processos.auradevcode.com.br/api/?endpoint=cadastrar_processo",
        json=p,
        headers={"Authorization": "Bearer CLAUDE_AUTOMACAO_123"}
    )
    print(p["cod_api"], r.json())',
                    'resposta' => '{ "id": 43, "status": "ok" }  # por processo
{ "erro": "Processo já cadastrado" }  # HTTP 409 se duplicado',
                ],
                [
                    'id' => 'listar',
                    'method' => 'GET', 'color' => 'primary',
                    'endpoint' => 'listar_processos',
                    'titulo' => 'Listar processos (paginado)',
                    'descricao' => 'Listagem paginada com filtros opcionais via query string: status, search, possui_ata, data_de, data_ate, pagina, limite.',
                    'payload' => 'GET ?endpoint=listar_processos&status=PENDENTE&pagina=1&limite=20',
                    'resposta' => '{
  "total": 100,
  "pagina": 1,
  "paginas": 5,
  "dados": [ ... ]
}',
                ],
                [
                    'id' => 'logs',
                    'method' => 'POST', 'color' => 'warning',
                    'endpoint' => 'logs',
                    'titulo' => 'Inserir log',
                    'descricao' => 'Registra uma entrada no histórico de logs do processo.',
                    'payload' => '{ "id_processo": 42, "mensagem": "ATA baixada com sucesso", "status": "INFO" }',
                    'resposta' => '{ "status": "ok" }',
                ],
                [
                    'id' => 'registrar-arquivo',
                    'method' => 'POST', 'color' => 'warning',
                    'endpoint' => 'registrar_arquivo',
                    'titulo' => 'Registrar arquivo (ATA)',
                    'descricao' => 'Registra metadados do arquivo na tabela processos_arquivos. Retorna o ID do registro para uso no upload.',
                    'payload' => '{
  "id_processo": 42,
  "nome_arquivo": "ata_1_TJMG.pdf",
  "caminho_arquivo": "/caminho/no/servidor/ata_1.pdf",
  "formato": "PDF",
  "tamanho_bytes": 102400,
  "texto_doc": "Aos vinte dias...",
  "indice": 1,
  "download_ok": 1
}',
                    'resposta' => '{ "id": 7, "status": "ok" }',
                ],
                [
                    'id' => 'upload-arquivo',
                    'method' => 'POST', 'color' => 'warning',
                    'endpoint' => 'upload_arquivo',
                    'titulo' => 'Upload de arquivo (multipart)',
                    'descricao' => 'Envia o binário do arquivo para o servidor. Usar após registrar_arquivo. Content-Type: multipart/form-data.',
                    'payload' => 'Form data: id_arquivo=7 + arquivo=<binary>',
                    'resposta' => '{ "status": "ok", "caminho": "/uploads/ata_1.pdf" }',
                ],
                [
                    'id' => 'download-id',
                    'method' => 'GET', 'color' => 'success',
                    'endpoint' => 'download_arquivo_id',
                    'titulo' => 'Download de arquivo por ID 🔓',
                    'descricao' => 'Serve o arquivo para download (Content-Disposition: attachment). Endpoint público — não exige token Bearer.',
                    'payload' => 'GET ?endpoint=download_arquivo_id&id=7',
                    'resposta' => '<binary — arquivo servido diretamente>',
                ],
                [
                    'id' => 'visualizar-id',
                    'method' => 'GET', 'color' => 'success',
                    'endpoint' => 'visualizar_arquivo_id',
                    'titulo' => 'Visualizar arquivo por ID 🔓',
                    'descricao' => 'Serve o arquivo para visualização inline no browser (Content-Disposition: inline). Endpoint público — não exige token Bearer.',
                    'payload' => 'GET ?endpoint=visualizar_arquivo_id&id=7',
                    'resposta' => '<binary — arquivo aberto inline no browser>',
                ],
            ];

            foreach ($endpoints as $ep): ?>
            <div class="accordion-item border-0 mb-2 shadow-sm" style="border-radius:10px;overflow:hidden">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed py-2" type="button"
                            data-bs-toggle="collapse" data-bs-target="#ep-<?= $ep['id'] ?>">
                        <span class="badge bg-<?= $ep['color'] ?> me-2"><?= $ep['method'] ?></span>
                        <code class="me-2 small"><?= $ep['endpoint'] ?></code>
                        <span class="text-muted small"><?= $ep['titulo'] ?></span>
                    </button>
                </h2>
                <div id="ep-<?= $ep['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#apiAccordion">
                    <div class="accordion-body pt-2">
                        <p class="small mb-3"><?= $ep['descricao'] ?></p>
                        <?php if ($ep['payload']): ?>
                        <div class="mb-2">
                            <span class="text-muted small fw-semibold">Payload / Parâmetros</span>
                            <pre class="bg-light rounded p-2 small mb-0" style="font-size:.78rem"><?= htmlspecialchars($ep['payload']) ?></pre>
                        </div>
                        <?php endif; ?>
                        <div>
                            <span class="text-muted small fw-semibold">Resposta</span>
                            <pre class="bg-light rounded p-2 small mb-0" style="font-size:.78rem"><?= htmlspecialchars($ep['resposta']) ?></pre>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

        </div>
    </div>

    <!-- ── BANCO DE DADOS ─────────────────────────────────────────────────── -->
    <div class="tab-pane fade" id="banco">
        <div class="row g-3">

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100" style="border-radius:12px">
                    <div class="card-header bg-white border-0 pt-3 pb-0 px-3">
                        <h6 class="fw-bold"><i class="bi bi-table text-primary me-1"></i>processos</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0 small">
                            <thead><tr><th class="ps-3">Coluna</th><th>Tipo</th><th>Observação</th></tr></thead>
                            <tbody>
                                <tr><td class="ps-3 font-monospace">id</td><td>INT PK AI</td><td></td></tr>
                                <tr><td class="ps-3 font-monospace">numero_processo</td><td>VARCHAR(50)</td><td>UNIQUE</td></tr>
                                <tr><td class="ps-3 font-monospace">cod_api</td><td>VARCHAR(100) NULL</td><td>Identificador do sistema externo — retornado no webhook como <code>id_integracao</code></td></tr>
                                <tr><td class="ps-3 font-monospace">tribunal</td><td>VARCHAR(10)</td><td>UF do estado, ex: 'MG'</td></tr>
                                <tr><td class="ps-3 font-monospace">tipo_sistema</td><td>VARCHAR(20)</td><td>PJE / EPROC / PROCON / DESCONHECIDO</td></tr>
                                <tr><td class="ps-3 font-monospace">data_ato</td><td>DATE NULL</td><td>Filtro temporal</td></tr>
                                <tr><td class="ps-3 font-monospace">status_consulta</td><td>VARCHAR(30)</td><td>Ver status acima</td></tr>
                                <tr><td class="ps-3 font-monospace">possui_ata</td><td>CHAR(1) NULL</td><td>S / N</td></tr>
                                <tr><td class="ps-3 font-monospace">qtd_atas</td><td>INT</td><td></td></tr>
                                <tr><td class="ps-3 font-monospace">caminho_arquivo</td><td>VARCHAR(500) NULL</td><td></td></tr>
                                <tr><td class="ps-3 font-monospace">mensagem_erro</td><td>TEXT NULL</td><td></td></tr>
                                <tr><td class="ps-3 font-monospace">data_ultima_consulta</td><td>DATETIME NULL</td><td></td></tr>
                                <tr><td class="ps-3 font-monospace">criado_em</td><td>DATETIME</td><td>DEFAULT NOW()</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
                    <div class="card-header bg-white border-0 pt-3 pb-0 px-3">
                        <h6 class="fw-bold"><i class="bi bi-table text-secondary me-1"></i>processos_logs</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0 small">
                            <thead><tr><th class="ps-3">Coluna</th><th>Tipo</th><th>Observação</th></tr></thead>
                            <tbody>
                                <tr><td class="ps-3 font-monospace">id</td><td>INT PK AI</td><td></td></tr>
                                <tr><td class="ps-3 font-monospace">id_processo</td><td>INT FK</td><td>→ processos.id</td></tr>
                                <tr><td class="ps-3 font-monospace">mensagem</td><td>TEXT</td><td></td></tr>
                                <tr><td class="ps-3 font-monospace">status</td><td>VARCHAR(20)</td><td>INFO / WARNING / ERROR</td></tr>
                                <tr><td class="ps-3 font-monospace">criado_em</td><td>DATETIME</td><td>DEFAULT NOW()</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
                    <div class="card-header bg-white border-0 pt-3 pb-0 px-3">
                        <h6 class="fw-bold"><i class="bi bi-table text-success me-1"></i>processos_arquivos</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0 small">
                            <thead><tr><th class="ps-3">Coluna</th><th>Tipo</th><th>Observação</th></tr></thead>
                            <tbody>
                                <tr><td class="ps-3 font-monospace">id</td><td>INT PK AI</td><td></td></tr>
                                <tr><td class="ps-3 font-monospace">id_processo</td><td>INT FK</td><td>→ processos.id</td></tr>
                                <tr><td class="ps-3 font-monospace">nome_arquivo</td><td>VARCHAR(255)</td><td></td></tr>
                                <tr><td class="ps-3 font-monospace">caminho_arquivo</td><td>VARCHAR(500)</td><td></td></tr>
                                <tr><td class="ps-3 font-monospace">formato</td><td>VARCHAR(10)</td><td>PDF / HTML / DOCX</td></tr>
                                <tr><td class="ps-3 font-monospace">tamanho_bytes</td><td>BIGINT</td><td></td></tr>
                                <tr><td class="ps-3 font-monospace">texto_doc</td><td>TEXT NULL</td><td>Texto extraído (500 chars)</td></tr>
                                <tr><td class="ps-3 font-monospace">indice</td><td>INT</td><td>Nº da ATA no processo</td></tr>
                                <tr><td class="ps-3 font-monospace">download_ok</td><td>TINYINT(1)</td><td>1=sucesso / 0=falhou</td></tr>
                                <tr><td class="ps-3 font-monospace">criado_em</td><td>DATETIME</td><td>DEFAULT NOW()</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card border-0 shadow-sm" style="border-radius:12px">
                    <div class="card-header bg-white border-0 pt-3 pb-0 px-3">
                        <h6 class="fw-bold"><i class="bi bi-table text-warning me-1"></i>robot_config</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0 small">
                            <thead><tr><th class="ps-3">Coluna</th><th>Tipo</th><th>Observação</th></tr></thead>
                            <tbody>
                                <tr><td class="ps-3 font-monospace">id</td><td>INT PK</td><td>Sempre = 1 (linha única)</td></tr>
                                <tr><td class="ps-3 font-monospace">ativo</td><td>TINYINT(1)</td><td>0=parado / 1=ativo</td></tr>
                                <tr><td class="ps-3 font-monospace">status</td><td>VARCHAR(50)</td><td>parado / aguardando / executando / erro</td></tr>
                                <tr><td class="ps-3 font-monospace">pid</td><td>INT NULL</td><td>PID do processo Python</td></tr>
                                <tr><td class="ps-3 font-monospace">ultimo_ciclo</td><td>DATETIME NULL</td><td></td></tr>
                                <tr><td class="ps-3 font-monospace">mensagem</td><td>VARCHAR(255) NULL</td><td></td></tr>
                                <tr><td class="ps-3 font-monospace">atualizado_em</td><td>DATETIME NULL</td><td></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="alert alert-info d-flex gap-2 align-items-center mb-0">
                    <i class="bi bi-database-gear fs-5"></i>
                    <div>
                        Para criar ou atualizar o banco em um novo ambiente, acesse o
                        <a href="<?= PAINEL_URL_BASE ?>migrations/" target="_blank" class="fw-semibold">runner de migrações</a>
                        e execute todas as migrações pendentes em ordem.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── CLASSIFICAÇÃO ──────────────────────────────────────────────────── -->
    <div class="tab-pane fade" id="classificacao">
        <div class="card border-0 shadow-sm" style="border-radius:12px">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-tags text-primary me-2"></i>Regras de classificação por tribunal</h5>
                <p class="text-muted small mb-4">
                    O tipo do processo é detectado automaticamente no momento do cadastro com base no 1º dígito numérico do número do processo e no tribunal.
                    Cada estado pode ter suas próprias regras — atualmente apenas MG (Minas Gerais) está implementado.
                </p>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 bg-light" style="border-radius:10px">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-light text-dark border fs-6">MG</span>
                                    <span class="badge bg-success">Implementado</span>
                                </div>
                                <table class="table table-sm mb-0 small">
                                    <thead><tr><th>1º dígito</th><th>Tipo</th><th>Scraper</th></tr></thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>5</strong></td>
                                            <td><?= tipoBadge('PJE') ?></td>
                                            <td><span class="badge bg-success">✓ Ativo</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>0, 1</strong></td>
                                            <td><?= tipoBadge('EPROC') ?></td>
                                            <td><span class="badge bg-warning text-dark">Pendente</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>2</strong></td>
                                            <td><?= tipoBadge('PROCON') ?></td>
                                            <td><span class="badge bg-warning text-dark">Pendente</span></td>
                                        </tr>
                                        <tr>
                                            <td><em>outro</em></td>
                                            <td><?= tipoBadge('DESCONHECIDO') ?></td>
                                            <td><span class="badge bg-secondary">—</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-center">
                        <div class="card border-dashed border-2 border-secondary w-100 text-center p-4" style="border-radius:10px;border-style:dashed!important">
                            <i class="bi bi-plus-circle fs-3 text-muted mb-2"></i>
                            <p class="text-muted small mb-0">Próximos tribunais a implementar</p>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mb-2">Como adicionar suporte a um novo tribunal</h6>
                <ol class="small">
                    <li class="mb-1">Crie <code>python/tribunais/{sigla}_{tipo}.py</code> herdando de <code>BaseScraper</code> e implemente o método <code>executar(numero_processo)</code>.</li>
                    <li class="mb-1">Adicione a entrada em <code>python/config.py → TRIBUNAIS_SUPORTADOS</code>.</li>
                    <li class="mb-1">Adicione a regra de classificação em <code>ProcessoRepositoryPDO::inferirTipo()</code> e <code>ProcessoModel::inferirTipo()</code>.</li>
                    <li class="mb-1">Adicione o tipo ao dict <code>SISTEMAS_SUPORTADOS</code> em <code>python/main.py</code>.</li>
                    <li>Adicione o tribunal à lista de opções no formulário de cadastro (<code>painel/Controllers/ProcessoController.php → $tribunais</code>).</li>
                </ol>
            </div>
        </div>
    </div>

</div>

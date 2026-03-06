<?php
require_once 'config.php';
requerLogin();

$paginaAtual  = 'documentacao.php';
$tituloPagina = 'Documentação';

include 'layout_header.php';
?>

<style>
/* ── Layout da documentação ───────────────────────────────────────────── */
.doc-card       { border:none; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.07); }
.nav-pills .nav-link          { color:#475569; font-weight:500; border-radius:8px; }
.nav-pills .nav-link.active   { background:#2563eb; }
.endpoint-card  { border-left:4px solid #2563eb; border-radius:0 8px 8px 0; background:#f8fafc; }
.endpoint-card.post { border-color:#10b981; }
.endpoint-card.get  { border-color:#3b82f6; }
.method-badge   { font-size:.7rem; font-weight:700; padding:.25rem .6rem; border-radius:6px; letter-spacing:.05em; }
.badge-get      { background:#dbeafe; color:#1d4ed8; }
.badge-post     { background:#d1fae5; color:#065f46; }
.code-block     { background:#0f172a; color:#94a3b8; border-radius:8px; font-size:.8rem; line-height:1.7; }
.param-table th { font-size:.75rem; text-transform:uppercase; letter-spacing:.05em; color:#64748b; border:none; }
.param-table td { font-size:.85rem; vertical-align:middle; border-color:#f1f5f9; }
.param-table code { background:#f1f5f9; color:#be185d; border-radius:4px; padding:.1rem .4rem; }
.arch-box       { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; font-family:monospace; font-size:.8rem; line-height:1.8; }
.section-title  { font-size:.7rem; text-transform:uppercase; letter-spacing:.1em; color:#94a3b8; font-weight:600; }
.highlight      { background:#fef3c7; border-radius:4px; padding:.1rem .3rem; }
.tip-box        { background:#eff6ff; border-left:3px solid #3b82f6; border-radius:0 8px 8px 0; padding:.75rem 1rem; font-size:.875rem; }
.warn-box       { background:#fef9c3; border-left:3px solid #f59e0b; border-radius:0 8px 8px 0; padding:.75rem 1rem; font-size:.875rem; }
</style>

<!-- Tabs principais -->
<ul class="nav nav-pills mb-4 gap-1" id="docTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-api">
            <i class="bi bi-plug-fill me-1"></i> API PHP — Endpoints
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-python">
            <i class="bi bi-robot me-1"></i> Automação Python
        </button>
    </li>
</ul>

<div class="tab-content">

<!-- ═══════════════════════════════════════════════════════════════════════
     TAB 1 — API PHP
═══════════════════════════════════════════════════════════════════════ -->
<div class="tab-pane fade show active" id="tab-api">

    <!-- Visão geral -->
    <div class="card doc-card mb-4 p-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary"></i> Visão Geral da API</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <p class="text-muted mb-2">A API é servida pelo <strong>index.php</strong> na raiz do projeto PHP. Todas as requisições usam o parâmetro <code>?endpoint=</code> na URL e devem conter o token de autenticação no cabeçalho HTTP.</p>
                <div class="tip-box mb-3">
                    <strong>URL base:</strong><br>
                    <code>http://localhost/processos_api/php/?endpoint=<em>{endpoint}</em></code>
                </div>
                <div class="warn-box">
                    <strong><i class="bi bi-shield-lock"></i> Autenticação obrigatória</strong><br>
                    Todas as requisições exigem o header:<br>
                    <code>Authorization: Bearer CLAUDE_AUTOMACAO_123</code>
                </div>
            </div>
            <div class="col-md-6">
                <div class="section-title mb-2">Resumo dos endpoints</div>
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr><th>Endpoint</th><th>Método</th><th>Função</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>processos_pendentes</code></td><td><span class="method-badge badge-get">GET</span></td><td>Busca fila</td></tr>
                        <tr><td><code>registrar_consulta</code></td><td><span class="method-badge badge-post">POST</span></td><td>Inicia consulta</td></tr>
                        <tr><td><code>registrar_ata</code></td><td><span class="method-badge badge-post">POST</span></td><td>Finaliza c/ ata</td></tr>
                        <tr><td><code>registrar_sem_ata</code></td><td><span class="method-badge badge-post">POST</span></td><td>Finaliza s/ ata</td></tr>
                        <tr><td><code>registrar_erro</code></td><td><span class="method-badge badge-post">POST</span></td><td>Marca erro</td></tr>
                        <tr><td><code>logs</code></td><td><span class="method-badge badge-post">POST</span></td><td>Insere log</td></tr>
                        <tr><td><code>status_processo</code></td><td><span class="method-badge badge-get">GET</span></td><td>Detalhe</td></tr>
                        <tr><td><code>cadastrar_processo</code></td><td><span class="method-badge badge-post">POST</span></td><td>Novo processo</td></tr>
                        <tr><td><code>listar_processos</code></td><td><span class="method-badge badge-get">GET</span></td><td>Lista + filtros</td></tr>
                        <tr><td><code>download_arquivo</code></td><td><span class="method-badge badge-get">GET</span></td><td>Download ATA</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Endpoints detalhados -->

    <!-- 1. processos_pendentes -->
    <div class="card doc-card endpoint-card get mb-3 p-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="method-badge badge-get">GET</span>
            <code class="fs-6">processos_pendentes</code>
            <span class="text-muted small ms-auto">Retorna até 10 processos com status PENDENTE</span>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="section-title mb-1">Parâmetros</div>
                <p class="text-muted small">Nenhum parâmetro necessário além do token.</p>
                <div class="section-title mb-1">Exemplo de requisição</div>
                <pre class="code-block p-3 mb-0">GET /php/?endpoint=processos_pendentes
Authorization: Bearer CLAUDE_AUTOMACAO_123</pre>
            </div>
            <div class="col-md-6">
                <div class="section-title mb-1">Resposta (200)</div>
                <pre class="code-block p-3 mb-0">[
  {
    "id": 15,
    "numero_processo": "5000213-62.2026.8.13.0521"
  },
  {
    "id": 16,
    "numero_processo": "0001234-56.2025.8.13.0100"
  }
]</pre>
                <small class="text-muted d-block mt-1">⚠ O campo <code>tribunal</code> deve ser adicionado à tabela para roteamento multi-tribunal.</small>
            </div>
        </div>
    </div>

    <!-- 2. registrar_consulta -->
    <div class="card doc-card endpoint-card post mb-3 p-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="method-badge badge-post">POST</span>
            <code class="fs-6">registrar_consulta</code>
            <span class="text-muted small ms-auto">Altera status de PENDENTE → CONSULTANDO</span>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="section-title mb-1">Body (JSON)</div>
                <table class="table param-table">
                    <thead><tr><th>Campo</th><th>Tipo</th><th>Obrigatório</th><th>Descrição</th></tr></thead>
                    <tbody>
                        <tr><td><code>id_processo</code></td><td>int</td><td>✅</td><td>ID do processo na tabela</td></tr>
                    </tbody>
                </table>
                <pre class="code-block p-3 mb-0">POST /php/?endpoint=registrar_consulta
Content-Type: application/json
Authorization: Bearer CLAUDE_AUTOMACAO_123

{ "id_processo": 15 }</pre>
            </div>
            <div class="col-md-6">
                <div class="section-title mb-1">Resposta (200)</div>
                <pre class="code-block p-3 mb-0">{ "status": "ok" }</pre>
                <div class="tip-box mt-2">Atualiza também <code>data_ultima_consulta = NOW()</code></div>
            </div>
        </div>
    </div>

    <!-- 3. registrar_ata -->
    <div class="card doc-card endpoint-card post mb-3 p-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="method-badge badge-post">POST</span>
            <code class="fs-6">registrar_ata</code>
            <span class="text-muted small ms-auto">Finaliza processo com ata encontrada</span>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="section-title mb-1">Body (JSON)</div>
                <table class="table param-table">
                    <thead><tr><th>Campo</th><th>Tipo</th><th>Descrição</th></tr></thead>
                    <tbody>
                        <tr><td><code>id_processo</code></td><td>int</td><td>ID do processo</td></tr>
                        <tr><td><code>qtd_atas</code></td><td>int</td><td>Quantidade de atas baixadas</td></tr>
                        <tr><td><code>arquivo</code></td><td>string</td><td>Nomes dos arquivos separados por <code> | </code></td></tr>
                    </tbody>
                </table>
                <pre class="code-block p-3 mb-0">{
  "id_processo": 15,
  "qtd_atas": 2,
  "arquivo": "TJMG_5000213_ATA_1_06032026.pdf | TJMG_5000213_ATA_2_06032026.html"
}</pre>
            </div>
            <div class="col-md-6">
                <div class="section-title mb-1">Campos atualizados no banco</div>
                <ul class="small text-muted">
                    <li><code>status_consulta</code> → <span class="badge bg-success">FINALIZADO</span></li>
                    <li><code>possui_ata</code> → <code>'S'</code></li>
                    <li><code>qtd_atas</code> → valor enviado</li>
                    <li><code>caminho_arquivo</code> → nomes dos arquivos</li>
                    <li><code>data_ultima_consulta</code> → NOW()</li>
                </ul>
                <pre class="code-block p-3 mb-0">{ "status": "ata registrada" }</pre>
            </div>
        </div>
    </div>

    <!-- 4. registrar_sem_ata -->
    <div class="card doc-card endpoint-card post mb-3 p-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="method-badge badge-post">POST</span>
            <code class="fs-6">registrar_sem_ata</code>
            <span class="text-muted small ms-auto">Finaliza processo sem ata encontrada</span>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <pre class="code-block p-3">{ "id_processo": 15 }</pre>
            </div>
            <div class="col-md-6">
                <ul class="small text-muted">
                    <li><code>status_consulta</code> → <span class="badge bg-success">FINALIZADO</span></li>
                    <li><code>possui_ata</code> → <code>'N'</code>, <code>qtd_atas</code> → 0</li>
                    <li><code>caminho_arquivo</code> → NULL</li>
                    <li><code>mensagem_erro</code> → NULL</li>
                </ul>
                <pre class="code-block p-3">{ "status": "processo finalizado sem ata" }</pre>
            </div>
        </div>
    </div>

    <!-- 5. registrar_erro -->
    <div class="card doc-card endpoint-card post mb-3 p-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="method-badge badge-post">POST</span>
            <code class="fs-6">registrar_erro</code>
            <span class="text-muted small ms-auto">Marca processo como ERRO</span>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <table class="table param-table">
                    <thead><tr><th>Campo</th><th>Obrigatório</th><th>Descrição</th></tr></thead>
                    <tbody>
                        <tr><td><code>id_processo</code></td><td>✅</td><td>ID do processo</td></tr>
                        <tr><td><code>mensagem_erro</code></td><td>❌</td><td>Descrição do erro (padrão: "Erro não informado")</td></tr>
                    </tbody>
                </table>
                <pre class="code-block p-3 mb-0">{
  "id_processo": 15,
  "mensagem_erro": "Timeout ao carregar página do TJMG"
}</pre>
            </div>
            <div class="col-md-6">
                <ul class="small text-muted">
                    <li><code>status_consulta</code> → <span class="badge bg-danger">ERRO</span></li>
                    <li><code>mensagem_erro</code> → texto recebido</li>
                </ul>
                <pre class="code-block p-3">{ "status": "erro registrado" }</pre>
            </div>
        </div>
    </div>

    <!-- 6. logs -->
    <div class="card doc-card endpoint-card post mb-3 p-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="method-badge badge-post">POST</span>
            <code class="fs-6">logs</code>
            <span class="text-muted small ms-auto">Insere novo registro na tabela processos_logs</span>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <table class="table param-table">
                    <thead><tr><th>Campo</th><th>Valores</th><th>Descrição</th></tr></thead>
                    <tbody>
                        <tr><td><code>id_processo</code></td><td>int</td><td>FK para tabela processos</td></tr>
                        <tr><td><code>mensagem</code></td><td>string</td><td>Texto do log</td></tr>
                        <tr><td><code>status</code></td><td><code>INFO</code> <code>ERROR</code> <code>WARNING</code></td><td>Nível do log</td></tr>
                    </tbody>
                </table>
                <pre class="code-block p-3 mb-0">{
  "id_processo": 15,
  "mensagem": "Ata baixada: TJMG_..._ATA_1.pdf",
  "status": "INFO"
}</pre>
            </div>
            <div class="col-md-6">
                <pre class="code-block p-3">{ "status": "log salvo" }</pre>
                <div class="tip-box mt-2">Usada pelo Python para rastreabilidade de cada etapa do scraping. Visível na página de detalhe do processo.</div>
            </div>
        </div>
    </div>

    <!-- 7. status_processo -->
    <div class="card doc-card endpoint-card get mb-3 p-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="method-badge badge-get">GET</span>
            <code class="fs-6">status_processo</code>
            <span class="text-muted small ms-auto">Retorna detalhe completo de um processo</span>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="section-title mb-1">Query string</div>
                <table class="table param-table">
                    <thead><tr><th>Param</th><th>Tipo</th><th>Descrição</th></tr></thead>
                    <tbody><tr><td><code>id</code></td><td>int</td><td>ID do processo</td></tr></tbody>
                </table>
                <pre class="code-block p-3 mb-0">GET /php/?endpoint=status_processo&id=15</pre>
            </div>
            <div class="col-md-6">
                <pre class="code-block p-3 mb-0">{
  "id": 15,
  "numero_processo": "5000213-62.2026.8.13.0521",
  "status_consulta": "FINALIZADO",
  "possui_ata": "S",
  "qtd_atas": 2,
  "caminho_arquivo": "TJMG_..._ATA_1.pdf | ...",
  "data_ultima_consulta": "2026-03-06 10:15:00",
  "criado_em": "2026-03-01 08:00:00",
  "mensagem_erro": null
}</pre>
            </div>
        </div>
    </div>

    <!-- 8. cadastrar_processo -->
    <div class="card doc-card endpoint-card post mb-3 p-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="method-badge badge-post">POST</span>
            <code class="fs-6">cadastrar_processo</code>
            <span class="text-muted small ms-auto">Insere novo processo com status PENDENTE</span>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <pre class="code-block p-3 mb-0">{ "numero_processo": "5000213-62.2026.8.13.0521" }</pre>
            </div>
            <div class="col-md-6">
                <table class="table param-table">
                    <thead><tr><th>HTTP</th><th>Resposta</th></tr></thead>
                    <tbody>
                        <tr><td>200</td><td><code>{"status":"processo cadastrado","id":16}</code></td></tr>
                        <tr><td>400</td><td><code>{"erro":"numero_processo é obrigatório"}</code></td></tr>
                        <tr><td>409</td><td><code>{"erro":"Processo já cadastrado"}</code></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 9. listar_processos -->
    <div class="card doc-card endpoint-card get mb-3 p-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="method-badge badge-get">GET</span>
            <code class="fs-6">listar_processos</code>
            <span class="text-muted small ms-auto">Lista processos com filtros e paginação</span>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="section-title mb-1">Parâmetros opcionais</div>
                <table class="table param-table">
                    <thead><tr><th>Param</th><th>Valores</th><th>Padrão</th></tr></thead>
                    <tbody>
                        <tr><td><code>status</code></td><td>PENDENTE / CONSULTANDO / FINALIZADO / ERRO</td><td>todos</td></tr>
                        <tr><td><code>search</code></td><td>texto</td><td>—</td></tr>
                        <tr><td><code>possui_ata</code></td><td>S / N</td><td>todos</td></tr>
                        <tr><td><code>data_de</code></td><td>YYYY-MM-DD</td><td>—</td></tr>
                        <tr><td><code>data_ate</code></td><td>YYYY-MM-DD</td><td>—</td></tr>
                        <tr><td><code>pagina</code></td><td>int ≥ 1</td><td>1</td></tr>
                        <tr><td><code>limite</code></td><td>1–100</td><td>20</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <div class="section-title mb-1">Resposta</div>
                <pre class="code-block p-3 mb-0">{
  "total": 45,
  "pagina": 1,
  "limite": 20,
  "paginas": 3,
  "dados": [
    {
      "id": 15,
      "numero_processo": "5000213...",
      "status_consulta": "FINALIZADO",
      "possui_ata": "S",
      "qtd_atas": 2,
      ...
    }
  ]
}</pre>
            </div>
        </div>
    </div>

    <!-- 10. download_arquivo -->
    <div class="card doc-card endpoint-card get mb-3 p-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="method-badge badge-get">GET</span>
            <code class="fs-6">download_arquivo</code>
            <span class="text-muted small ms-auto">Download do arquivo de ata do processo</span>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <pre class="code-block p-3">GET /php/?endpoint=download_arquivo&id=15</pre>
                <div class="section-title mb-1 mt-2">MIME types suportados</div>
                <table class="table param-table">
                    <thead><tr><th>Extensão</th><th>Content-Type</th></tr></thead>
                    <tbody>
                        <tr><td><code>.pdf</code></td><td><code>application/pdf</code></td></tr>
                        <tr><td><code>.html</code></td><td>Servido via browser</td></tr>
                        <tr><td><code>.docx</code></td><td>Word OpenXML</td></tr>
                        <tr><td>outros</td><td><code>application/octet-stream</code></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <div class="section-title mb-1">Erros possíveis</div>
                <table class="table param-table">
                    <thead><tr><th>HTTP</th><th>Mensagem</th></tr></thead>
                    <tbody>
                        <tr><td>400</td><td>id do processo é obrigatório</td></tr>
                        <tr><td>404</td><td>Processo não encontrado</td></tr>
                        <tr><td>404</td><td>Este processo não possui arquivo</td></tr>
                        <tr><td>404</td><td>Arquivo não encontrado no disco</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div><!-- /tab-api -->

<!-- ═══════════════════════════════════════════════════════════════════════
     TAB 2 — PYTHON
═══════════════════════════════════════════════════════════════════════ -->
<div class="tab-pane fade" id="tab-python">

    <!-- Visão geral -->
    <div class="card doc-card mb-4 p-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-robot text-primary"></i> Visão Geral da Automação</h5>
        <p class="text-muted">O módulo Python automatiza a consulta de processos jurídicos em portais de tribunais brasileiros, identificando e baixando atas de audiência. É desacoplado do painel e se comunica exclusivamente via API.</p>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="section-title mb-2">Stack</div>
                <ul class="list-unstyled small">
                    <li><i class="bi bi-check text-success"></i> Python 3.10+</li>
                    <li><i class="bi bi-check text-success"></i> Selenium 4 (Chrome)</li>
                    <li><i class="bi bi-check text-success"></i> webdriver-manager</li>
                    <li><i class="bi bi-check text-success"></i> requests</li>
                    <li><i class="bi bi-check text-success"></i> logging padrão Python</li>
                    <li><i class="bi bi-check text-success"></i> dataclasses + typing</li>
                </ul>
            </div>
            <div class="col-md-4">
                <div class="section-title mb-2">Tribunais suportados</div>
                <table class="table param-table">
                    <thead><tr><th>Sigla</th><th>Sistema</th><th>Status</th></tr></thead>
                    <tbody>
                        <tr><td>TJMG</td><td>PJe Consulta Pública</td><td><span class="badge bg-success">Ativo</span></td></tr>
                        <tr><td>TJSP</td><td>eSAJ</td><td><span class="badge bg-secondary">Futuro</span></td></tr>
                        <tr><td>TRF5</td><td>PJe</td><td><span class="badge bg-secondary">Futuro</span></td></tr>
                        <tr><td>TJSE</td><td>PJe</td><td><span class="badge bg-secondary">Futuro</span></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-4">
                <div class="section-title mb-2">Palavras-chave de ata</div>
                <div class="d-flex flex-wrap gap-1 mb-2">
                    <?php
                    $atas = ['ata','ata de audiência','ata audiencia','ata sem sentença','termo de audiência','termo audiencia','assentada'];
                    foreach($atas as $p):
                    ?><span class="badge bg-success"><?= htmlspecialchars($p) ?></span><?php endforeach; ?>
                </div>
                <div class="section-title mb-2">Ignorados</div>
                <div class="d-flex flex-wrap gap-1">
                    <?php
                    $ignore = ['decisão','despacho','certidão','petição','mandado','ofício'];
                    foreach($ignore as $p):
                    ?><span class="badge bg-danger"><?= htmlspecialchars($p) ?></span><?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Estrutura de arquivos -->
    <div class="card doc-card mb-4 p-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-folder2-open text-warning"></i> Estrutura de Arquivos</h5>
        <div class="row g-4">
            <div class="col-lg-6">
                <pre class="arch-box p-3">python/
├── <strong>main.py</strong>              <span class="text-muted"># Entrada + CLI + orquestrador</span>
├── <strong>config.py</strong>            <span class="text-muted"># Configurações globais</span>
├── <strong>servidor.py</strong>          <span class="text-muted"># Servidor de status (porta 8888)</span>
├── requirements.txt     <span class="text-muted"># selenium, requests, webdriver-manager</span>
│
├── core/
│   ├── <strong>api_client.py</strong>    <span class="text-muted"># Comunicação com API PHP</span>
│   ├── <strong>downloader.py</strong>    <span class="text-muted"># Download PDF e HTML</span>
│   ├── <strong>driver_factory.py</strong><span class="text-muted"># WebDriver Chrome</span>
│   ├── <strong>logger_setup.py</strong>  <span class="text-muted"># Logging console + arquivo</span>
│   └── <strong>utils.py</strong>         <span class="text-muted"># Classificação + relatório</span>
│
├── tribunais/
│   ├── <strong>base_scraper.py</strong>  <span class="text-muted"># Contrato ABC (interface)</span>
│   └── <strong>tjmg_pje.py</strong>      <span class="text-muted"># Implementação TJMG</span>
│
├── models/
│   └── <strong>documento.py</strong>     <span class="text-muted"># Dataclass Documento</span>
│
├── logs/
│   └── automacao.log    <span class="text-muted"># Rotativo 5MB × 3 backups</span>
│
└── downloads/
    └── TJMG/            <span class="text-muted"># PDFs e HTMLs por tribunal</span></pre>
            </div>
            <div class="col-lg-6">
                <div class="section-title mb-2">Responsabilidade de cada módulo</div>
                <div class="accordion accordion-flush" id="accModulos">
                    <?php
                    $modulos = [
                        ['main.py', 'bi-play-circle', 'primary', 'Ponto de entrada. Lê argumentos CLI (--loop, --headless, --download-dir, --log-level), instancia APIClient e Downloader, carrega o scraper correto via factory e executa o fluxo completo por processo.'],
                        ['config.py', 'bi-gear', 'secondary', 'Único arquivo com todas as constantes: URL da API, token, timeouts, diretórios, palavras-chave de ata e mapeamento de tribunais para scrapers.'],
                        ['core/api_client.py', 'bi-plug', 'info', 'Encapsula todas as chamadas HTTP à API PHP local. Métodos: buscar_processos_pendentes(), registrar_consulta(), registrar_log(), registrar_ata(), registrar_erro(), status_processo(). Nunca lança exceções para o chamador.'],
                        ['core/downloader.py', 'bi-download', 'success', 'Baixa PDFs via requests com cookies copiados do Selenium (mantém sessão autenticada). Baixa HTMLs da mesma forma. Gera nome padronizado: TRIBUNAL_processo_ATA_indice_data.ext'],
                        ['core/driver_factory.py', 'bi-browser-chrome', 'warning', 'Cria instância do Chrome via webdriver-manager. Suporta modo headless. Remove assinatura de automação (navigator.webdriver) para evitar bloqueios.'],
                        ['core/utils.py', 'bi-tools', 'secondary', 'classificar_documento(): verifica palavras-chave de ata vs. lista de ignorados (case-insensitive, sem acentos). imprimir_relatorio(): exibe resumo final no console.'],
                        ['tribunais/base_scraper.py', 'bi-diagram-2', 'primary', 'Classe abstrata (ABC) que define o contrato para todos os scrapers: abrir_consulta(), pesquisar_processo(), abrir_detalhe(), mapear_documentos(), detectar_bloqueio(). O método executar() orquestra o fluxo completo.'],
                        ['tribunais/tjmg_pje.py', 'bi-geo-alt', 'danger', 'Implementação concreta para o PJe TJMG (pje-consulta-publica.tjmg.jus.br). Múltiplos seletores CSS/XPath como fallback. Extrai URL de detalhe via regex nos atributos onclick. Mapeia documentos por idBin (PDF) e openPopUp (HTML).'],
                        ['models/documento.py', 'bi-file-earmark-text', 'secondary', 'Dataclass com todos os campos de um documento: tribunal, numero_processo, texto, url, formato (pdf/html), eh_ata, indice, nome_arquivo, caminho_arquivo, tamanho_bytes, download_ok.'],
                    ];
                    foreach($modulos as $i => $m):
                    ?>
                    <div class="accordion-item border-0 border-bottom">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed py-2 px-0 bg-transparent shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mod<?= $i ?>">
                                <i class="bi <?= $m[1] ?> text-<?= $m[2] ?> me-2"></i>
                                <code class="small"><?= htmlspecialchars($m[0]) ?></code>
                            </button>
                        </h2>
                        <div id="mod<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#accModulos">
                            <div class="accordion-body py-2 px-0 small text-muted"><?= htmlspecialchars($m[3]) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Fluxo de execução -->
    <div class="card doc-card mb-4 p-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-diagram-3 text-success"></i> Fluxo de Execução por Processo</h5>
        <div class="row g-2">
            <?php
            $etapas = [
                ['0', 'primary', 'bi-cloud-download', 'Buscar processo', 'GET /processos_pendentes → seleciona primeiro da fila → POST /registrar_consulta → POST /logs "Consulta iniciada"'],
                ['1', 'info', 'bi-cpu', 'Escolher scraper', 'Factory carrega o scraper correto via config.TRIBUNAIS_SUPORTADOS. Se tribunal não suportado → registrar_erro() e encerrar.'],
                ['2', 'warning', 'bi-browser-chrome', 'Abrir portal', 'Selenium abre URL do tribunal (ex: pje-consulta-publica.tjmg.jus.br). Detecta bloqueios: captcha, acesso negado, redirecionamento.'],
                ['3', 'warning', 'bi-search', 'Pesquisar', 'Preenche campo de número do processo. Submete. Aguarda resultados. Se não encontrado → registrar_erro().'],
                ['4', 'warning', 'bi-box-arrow-in-right', 'Abrir detalhe', 'Extrai URL de detalhe via regex no onclick (openPopUp). Navega diretamente. Se falhar → clicar no resultado.'],
                ['5', 'secondary', 'bi-list-ul', 'Mapear documentos', 'Percorre todos os links da página de detalhe. Identifica método: PDF (idBin no href) ou HTML (onclick com openPopUp/documentoSemLoginHTML).'],
                ['6', 'secondary', 'bi-tags', 'Classificar', 'classificar_documento() verifica palavras-chave. Documentos com termos ignorados nunca são atas. Documentos com termos de ata são marcados como eh_ata=True.'],
                ['7', 'success', 'bi-cloud-arrow-down', 'Download', 'Copia cookies do Selenium para requests.Session. Baixa cada ata. Nomeia como TRIBUNAL_processo_ATA_N_DDMMAAAA.ext. Registra log por arquivo.'],
                ['8', 'success', 'bi-send-check', 'Registrar resultado', 'Se houver atas baixadas → POST /registrar_ata com nomes. POST /logs "Registro enviado". Imprime relatório final no console.'],
            ];
            foreach($etapas as $e):
            ?>
            <div class="col-md-4">
                <div class="p-3 border rounded-3 h-100" style="border-color:#e2e8f0!important">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-<?= $e[1] ?>"><?= $e[0] ?></span>
                        <i class="bi <?= $e[2] ?> text-<?= $e[1] ?>"></i>
                        <strong class="small"><?= htmlspecialchars($e[3]) ?></strong>
                    </div>
                    <p class="small text-muted mb-0"><?= htmlspecialchars($e[4]) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- CLI e configuração -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card doc-card p-4 h-100">
                <h5 class="fw-bold mb-3"><i class="bi bi-terminal text-dark"></i> Linha de Comando</h5>
                <table class="table param-table">
                    <thead><tr><th>Argumento</th><th>Descrição</th></tr></thead>
                    <tbody>
                        <tr><td><code>--loop</code></td><td>Processa fila completa até esvaziar</td></tr>
                        <tr><td><code>--headless</code></td><td>Chrome sem janela (modo servidor)</td></tr>
                        <tr><td><code>--download-dir DIR</code></td><td>Pasta de destino dos arquivos</td></tr>
                        <tr><td><code>--log-level LEVEL</code></td><td>DEBUG / INFO / WARNING / ERROR</td></tr>
                    </tbody>
                </table>
                <div class="section-title mb-2 mt-3">Exemplos</div>
                <pre class="code-block p-3 mb-0"># Processa 1 processo (Chrome visível)
python main.py

# Fila completa sem janela (produção)
python main.py --headless --loop

# Diagnóstico completo
python main.py --log-level DEBUG

# Download em pasta específica
python main.py --download-dir "D:/atas"</pre>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card doc-card p-4 h-100">
                <h5 class="fw-bold mb-3"><i class="bi bi-gear text-secondary"></i> config.py — Parâmetros</h5>
                <table class="table param-table">
                    <thead><tr><th>Constante</th><th>Padrão</th><th>Descrição</th></tr></thead>
                    <tbody>
                        <tr><td><code>API_BASE_URL</code></td><td><code>localhost/processos_api/php</code></td><td>URL da API PHP</td></tr>
                        <tr><td><code>API_TOKEN</code></td><td><code>CLAUDE_AUTOMACAO_123</code></td><td>Bearer token</td></tr>
                        <tr><td><code>API_TIMEOUT</code></td><td>30s</td><td>Timeout requisições</td></tr>
                        <tr><td><code>SELENIUM_EXPLICIT_WAIT</code></td><td>10s</td><td>Espera por elementos</td></tr>
                        <tr><td><code>PAGE_LOAD_TIMEOUT</code></td><td>30s</td><td>Carregamento de página</td></tr>
                        <tr><td><code>DEFAULT_DOWNLOAD_DIR</code></td><td><code>python/downloads/</code></td><td>Pasta de downloads</td></tr>
                        <tr><td><code>LOOP_INTERVAL_SECONDS</code></td><td>5s</td><td>Pausa entre ciclos</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Adicionar novo tribunal -->
    <div class="card doc-card p-4 mb-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-plus-circle text-success"></i> Como Adicionar um Novo Tribunal</h5>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="section-title mb-2">Passo 1 — Criar o scraper</div>
                <pre class="code-block p-3 mb-0"><span class="text-green-400"># tribunais/tjsp_esaj.py</span>
from tribunais.base_scraper import BaseScraper
from models.documento import Documento
from typing import List

class TJSPEsajScraper(BaseScraper):

    @property
    def nome_tribunal(self) -> str:
        return "TJSP"

    @property
    def url_consulta(self) -> str:
        return "https://esaj.tjsp.jus.br/..."

    def abrir_consulta(self) -> bool:
        # Navega para o portal
        ...

    def pesquisar_processo(self, num: str) -> bool:
        # Preenche e submete formulário
        ...

    def abrir_detalhe(self, num: str) -> bool:
        # Navega para detalhe
        ...

    def mapear_documentos(self, num: str) -> List[Documento]:
        # Retorna lista de Documento
        ...

    def detectar_bloqueio(self) -> str:
        # Retorna "" ou descrição do bloqueio
        ...</pre>
            </div>
            <div class="col-md-6">
                <div class="section-title mb-2">Passo 2 — Registrar em config.py</div>
                <pre class="code-block p-3 mb-0">TRIBUNAIS_SUPORTADOS = {
    "TJMG": "tribunais.tjmg_pje.TJMGPJeScraper",
    <span style="color:#86efac">"TJSP": "tribunais.tjsp_esaj.TJSPEsajScraper",</span>
}</pre>
                <div class="tip-box mt-3">
                    <strong>Sem mais alterações!</strong> O <code>main.py</code> carrega o scraper via <code>importlib.import_module()</code> automaticamente baseado no campo <code>tribunal</code> da API.
                </div>
                <div class="section-title mb-2 mt-3">Passo 3 — Banco de dados</div>
                <p class="small text-muted">O campo <code>tribunal</code> na tabela <code>processos</code> controla qual scraper será usado. Certifique-se de que os processos cadastrados têm o valor correto (ex: <code>TJSP</code>).</p>
                <div class="warn-box">
                    O endpoint <code>processos_pendentes</code> precisa retornar o campo <code>tribunal</code> no JSON para que o roteamento funcione. Adicione a coluna à tabela e ao SELECT se ainda não existir.
                </div>
            </div>
        </div>
    </div>

    <!-- Tratamento de erros -->
    <div class="card doc-card p-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-shield-exclamation text-danger"></i> Tratamento de Erros e Bloqueios</h5>
        <div class="row g-3">
            <?php
            $erros = [
                ['CAPTCHA', 'danger', 'Detectado via iframe com src contendo "recaptcha", ou elementos .g-recaptcha na página. Registra erro remoto e encerra o processo atual.'],
                ['Acesso negado', 'danger', 'HTTP 401/403 ou texto "acesso negado" / "forbidden" no título da página. Interrompe o fluxo sem tentar novamente.'],
                ['Redirecionamento para login', 'warning', 'URL atual contém "login" sem "consulta". Indica sessão expirada. Encerra com erro registrado.'],
                ['Processo não encontrado', 'warning', 'Pesquisa retorna 0 resultados ou mensagem como "nenhum processo". Registra log INFO e pula para o próximo.'],
                ['TimeoutException', 'secondary', 'Capturada por WebDriverWait. Cada etapa tem timeout próprio (10s padrão). Registra erro e encerra o processo.'],
                ['Stale element', 'secondary', 'StaleElementReferenceException capturada durante mapeamento de documentos — o elemento é simplesmente ignorado.'],
                ['Erro de rede (download)', 'info', 'requests.RequestException capturada por arquivo. Registra erro remoto e continua tentando os próximos arquivos.'],
                ['Erro inesperado', 'secondary', 'Bloco try/except genérico em processar_um(). Registra via registrar_erro() e imprime relatório de ERRO. Não derruba o loop.'],
            ];
            foreach($erros as $er):
            ?>
            <div class="col-md-6">
                <div class="d-flex gap-2 p-2 border rounded-3" style="border-color:#e2e8f0!important">
                    <span class="badge bg-<?= $er[1] ?> align-self-start mt-1"><?= htmlspecialchars($er[0]) ?></span>
                    <p class="small text-muted mb-0"><?= htmlspecialchars($er[2]) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div><!-- /tab-python -->
</div><!-- /tab-content -->

<?php include 'layout_footer.php'; ?>

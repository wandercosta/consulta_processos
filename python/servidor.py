"""
Servidor HTTP de status e controle da automação de processos.

Expõe uma interface web leve na porta 8888 que permite:
  - Visualizar o status da automação
  - Ver configurações ativas
  - Iniciar a automação via botão
  - Monitorar logs recentes

Uso:
    python servidor.py
    python servidor.py --port 8888

Compatível com preview_start do Claude Code.
"""

import sys
import os

# Garante que a pasta do projeto esteja no sys.path
_dir = os.path.dirname(os.path.abspath(__file__))
if _dir not in sys.path:
    sys.path.insert(0, _dir)

import argparse
import html
import json
import subprocess
import threading
from datetime import datetime
from http.server import BaseHTTPRequestHandler, HTTPServer
from urllib.parse import urlparse, parse_qs

import config

PORT_PADRAO = 8888

# Estado compartilhado da automação (thread-safe via lock)
_lock = threading.Lock()
_estado = {
    "em_execucao": False,
    "ultimo_inicio": None,
    "ultimo_fim": None,
    "ultimo_modo": None,
    "log_recente": [],
}


def _adicionar_log(mensagem: str) -> None:
    with _lock:
        ts = datetime.now().strftime("%H:%M:%S")
        _estado["log_recente"].append(f"[{ts}] {mensagem}")
        if len(_estado["log_recente"]) > 50:
            _estado["log_recente"].pop(0)


def _executar_automacao(modo: str) -> None:
    """
    Executa main.py em subprocess com captura de output em tempo real.

    Usa subprocess.Popen + threads de leitura para que cada linha de log
    apareça no painel à medida que é produzida (a página recarrega a cada 10s).
    Tanto stdout quanto stderr são capturados — erros Python ficam visíveis.
    """
    with _lock:
        if _estado["em_execucao"]:
            return
        _estado["em_execucao"] = True
        _estado["ultimo_inicio"] = datetime.now().strftime("%d/%m/%Y %H:%M:%S")
        _estado["ultimo_modo"] = modo
        _estado["ultimo_fim"] = None

    # PYTHONUNBUFFERED=1 garante que o subprocesso não bufferize o stdout
    env = os.environ.copy()
    env["PYTHONUNBUFFERED"] = "1"
    env["PYTHONUTF8"] = "1"

    args_map = {
        "simples":        [sys.executable, "-u", "main.py"],
        "loop":           [sys.executable, "-u", "main.py", "--loop"],
        "headless":       [sys.executable, "-u", "main.py", "--headless"],
        "headless_loop":  [sys.executable, "-u", "main.py", "--headless", "--loop"],
    }
    cmd = args_map.get(modo, [sys.executable, "-u", "main.py"])
    _adicionar_log(f"▶ Iniciando automação (modo: {modo})")

    proc = None
    try:
        proc = subprocess.Popen(
            cmd,
            cwd=_dir,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True,
            encoding="utf-8",
            errors="replace",
            env=env,
        )

        def _ler_stream(stream, prefixo: str = "") -> None:
            """Lê linhas do stream e adiciona ao log em tempo real."""
            for linha in stream:
                linha = linha.rstrip("\r\n")
                if linha.strip():
                    _adicionar_log(f"{prefixo}{linha}")

        # Lê stdout e stderr em threads separadas para evitar deadlock
        t_out = threading.Thread(target=_ler_stream, args=(proc.stdout, ""), daemon=True)
        t_err = threading.Thread(target=_ler_stream, args=(proc.stderr, "⚠ STDERR: "), daemon=True)
        t_out.start()
        t_err.start()

        t_out.join()
        t_err.join()
        proc.wait()

        if proc.returncode != 0:
            _adicionar_log(f"⚠ Processo encerrou com código {proc.returncode}")
        else:
            _adicionar_log("✓ Processo concluído com sucesso")

    except Exception as e:
        _adicionar_log(f"⚠ ERRO ao iniciar subprocesso: {e}")
        if proc:
            try:
                proc.kill()
            except Exception:
                pass
    finally:
        with _lock:
            _estado["em_execucao"] = False
            _estado["ultimo_fim"] = datetime.now().strftime("%d/%m/%Y %H:%M:%S")
        _adicionar_log("■ Automação encerrada")


def _pagina_html(porta: int) -> str:
    """Gera a página de status em HTML."""
    with _lock:
        em_exec = _estado["em_execucao"]
        ultimo_inicio = _estado["ultimo_inicio"] or "—"
        ultimo_fim    = _estado["ultimo_fim"]    or ("Em andamento..." if em_exec else "—")
        ultimo_modo   = _estado["ultimo_modo"]   or "—"
        logs = list(_estado["log_recente"])

    status_badge = (
        '<span class="badge bg-warning text-dark fs-6"><i class="bi bi-arrow-repeat"></i> Executando</span>'
        if em_exec else
        '<span class="badge bg-success fs-6"><i class="bi bi-check-circle"></i> Aguardando</span>'
    )

    logs_html = "\n".join(
        f'<div class="log-linha {"text-danger fw-bold" if "ERRO" in l else "text-warning" if "AVISO" in l else ""}">'
        f'{html.escape(l)}</div>'
        for l in reversed(logs)
    ) or '<div class="text-muted">Nenhum log ainda.</div>'

    tribunais_html = "".join(
        f'<span class="badge bg-primary me-1">{html.escape(t)}</span>'
        for t in config.TRIBUNAIS_SUPORTADOS
    )

    botoes_disabled = 'disabled' if em_exec else ''

    return f"""<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="refresh" content="10">
  <title>Automação — Processos Jurídicos</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body {{ background: #f1f5f9; font-family: 'Segoe UI', sans-serif; }}
    .card {{ border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }}
    .log-box {{ background: #0f172a; color: #94a3b8; font-family: monospace; font-size: .8rem;
                height: 280px; overflow-y: auto; border-radius: 8px; padding: 1rem; }}
    .log-linha {{ line-height: 1.6; }}
    .stat-item {{ background: linear-gradient(135deg,#3b82f6,#2563eb); color:#fff;
                  border-radius: 10px; padding: 1rem 1.25rem; }}
  </style>
</head>
<body>
<div class="container-fluid py-4 px-4">

  <!-- Header -->
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h4 class="mb-0 fw-bold"><i class="bi bi-robot text-primary"></i> Automação de Processos Jurídicos</h4>
      <small class="text-muted">Porta {porta} &nbsp;·&nbsp; Atualização automática a cada 10s</small>
    </div>
    <div>{status_badge}</div>
  </div>

  <!-- Cards de info -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="stat-item">
        <div class="small opacity-75">API Base URL</div>
        <div class="fw-semibold text-truncate">{html.escape(config.API_BASE_URL)}</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-item" style="background:linear-gradient(135deg,#10b981,#059669)">
        <div class="small opacity-75">Tribunais Suportados</div>
        <div class="fw-semibold">{tribunais_html}</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-item" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed)">
        <div class="small opacity-75">Último Início</div>
        <div class="fw-semibold">{html.escape(ultimo_inicio)}</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-item" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
        <div class="small opacity-75">Último Fim / Modo</div>
        <div class="fw-semibold">{html.escape(ultimo_fim)} <span class="badge bg-light text-dark">{html.escape(ultimo_modo)}</span></div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Controles -->
    <div class="col-lg-4">
      <div class="card p-3 h-100">
        <h6 class="fw-bold mb-3"><i class="bi bi-play-circle text-primary"></i> Executar Automação</h6>
        <div class="d-grid gap-2">
          <form method="POST" action="/executar">
            <input type="hidden" name="modo" value="simples">
            <button type="submit" class="btn btn-primary w-100 {botoes_disabled}" {botoes_disabled}>
              <i class="bi bi-play"></i> Processar 1 (com Chrome)
            </button>
          </form>
          <form method="POST" action="/executar">
            <input type="hidden" name="modo" value="loop">
            <button type="submit" class="btn btn-outline-primary w-100 {botoes_disabled}" {botoes_disabled}>
              <i class="bi bi-arrow-repeat"></i> Processar Fila Completa
            </button>
          </form>
          <form method="POST" action="/executar">
            <input type="hidden" name="modo" value="headless">
            <button type="submit" class="btn btn-outline-secondary w-100 {botoes_disabled}" {botoes_disabled}>
              <i class="bi bi-eye-slash"></i> Processar 1 (headless)
            </button>
          </form>
          <form method="POST" action="/executar">
            <input type="hidden" name="modo" value="headless_loop">
            <button type="submit" class="btn btn-success w-100 {botoes_disabled}" {botoes_disabled}>
              <i class="bi bi-server"></i> Fila Completa (headless)
            </button>
          </form>
        </div>

        <hr>
        <h6 class="fw-bold mb-2"><i class="bi bi-gear text-secondary"></i> Configuração Ativa</h6>
        <table class="table table-sm table-borderless mb-0">
          <tr><td class="text-muted small">Token</td><td class="font-monospace small">••••••123</td></tr>
          <tr><td class="text-muted small">Timeout API</td><td class="small">{config.API_TIMEOUT}s</td></tr>
          <tr><td class="text-muted small">Espera Selenium</td><td class="small">{config.SELENIUM_EXPLICIT_WAIT}s</td></tr>
          <tr><td class="text-muted small">Intervalo loop</td><td class="small">{config.LOOP_INTERVAL_SECONDS}s</td></tr>
          <tr><td class="text-muted small">Downloads</td><td class="small text-truncate">{html.escape(config.DEFAULT_DOWNLOAD_DIR)}</td></tr>
        </table>
      </div>
    </div>

    <!-- Log -->
    <div class="col-lg-8">
      <div class="card p-3 h-100">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="fw-bold mb-0"><i class="bi bi-terminal text-success"></i> Log em Tempo Real</h6>
          <a href="/limpar" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-trash"></i> Limpar
          </a>
        </div>
        <div class="log-box" id="logBox">
          {logs_html}
        </div>
      </div>
    </div>
  </div>

  <div class="text-center text-muted small mt-4">
    <i class="bi bi-info-circle"></i>
    Para parar o servidor: <kbd>Ctrl+C</kbd> no terminal &nbsp;·&nbsp;
    Logs em <code>logs/automacao.log</code>
  </div>
</div>
</body>
</html>"""


class Handler(BaseHTTPRequestHandler):
    """Handler HTTP minimalista para o servidor de status."""

    porta: int = PORT_PADRAO

    def do_GET(self) -> None:
        parsed = urlparse(self.path)

        if parsed.path == "/limpar":
            with _lock:
                _estado["log_recente"].clear()
            self._redirecionar("/")
            return

        if parsed.path == "/status.json":
            with _lock:
                dados = dict(_estado)
            self._json(dados)
            return

        # Página principal
        body = _pagina_html(self.porta).encode("utf-8")
        self.send_response(200)
        self.send_header("Content-Type", "text/html; charset=utf-8")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def do_POST(self) -> None:
        parsed = urlparse(self.path)

        if parsed.path == "/executar":
            tamanho = int(self.headers.get("Content-Length", 0))
            corpo = self.rfile.read(tamanho).decode("utf-8")
            params = parse_qs(corpo)
            modo = params.get("modo", ["simples"])[0]

            with _lock:
                em_exec = _estado["em_execucao"]

            if not em_exec:
                t = threading.Thread(target=_executar_automacao, args=(modo,), daemon=True)
                t.start()

        self._redirecionar("/")

    def _redirecionar(self, destino: str) -> None:
        self.send_response(302)
        self.send_header("Location", destino)
        self.end_headers()

    def _json(self, dados: dict) -> None:
        body = json.dumps(dados, ensure_ascii=False, default=str).encode("utf-8")
        self.send_response(200)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def log_message(self, fmt: str, *args) -> None:
        # Silencia logs de acesso HTTP no terminal
        pass


def main() -> None:
    parser = argparse.ArgumentParser(description="Servidor de status da automação")
    parser.add_argument("--port", type=int, default=PORT_PADRAO)
    args = parser.parse_args()

    Handler.porta = args.port
    servidor = HTTPServer(("localhost", args.port), Handler)

    _adicionar_log(f"Servidor iniciado em http://localhost:{args.port}")
    _adicionar_log(f"API: {config.API_BASE_URL}")

    try:
        servidor.serve_forever()
    except KeyboardInterrupt:
        pass


if __name__ == "__main__":
    main()

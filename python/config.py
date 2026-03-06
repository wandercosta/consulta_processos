"""
Configurações globais do sistema de automação de processos jurídicos.

Centraliza todas as constantes e parâmetros configuráveis.
Altere aqui para adaptar o sistema ao ambiente de produção.
"""

import os

# Diretório base do projeto Python (onde este arquivo está)
BASE_DIR = os.path.dirname(os.path.abspath(__file__))

# ─── API Local PHP ────────────────────────────────────────────────────────────
# URL base da API PHP. Ajuste conforme configuração do servidor local.
# Se o roteador PHP usar ?endpoint=..., altere para "http://localhost/processos_api"
# e ajuste o método _build_url em api_client.py.
API_BASE_URL = "http://localhost/processos_api/php"
API_TOKEN = "CLAUDE_AUTOMACAO_123"
API_TIMEOUT = 30  # segundos

# ─── Selenium ─────────────────────────────────────────────────────────────────
SELENIUM_EXPLICIT_WAIT = 10   # segundos de espera para elementos
PAGE_LOAD_TIMEOUT = 30        # segundos para carregamento de página

# ─── Diretórios ───────────────────────────────────────────────────────────────
DEFAULT_DOWNLOAD_DIR = os.path.join(BASE_DIR, "downloads")
LOG_DIR = os.path.join(BASE_DIR, "logs")
LOG_FILE = os.path.join(LOG_DIR, "automacao.log")

# ─── Logging ──────────────────────────────────────────────────────────────────
LOG_FORMAT = "[%(asctime)s] %(levelname)-7s %(message)s"
LOG_DATE_FORMAT = "%Y-%m-%d %H:%M:%S"

# ─── Classificação de documentos ──────────────────────────────────────────────
# Termos que identificam atas de audiência (case-insensitive, sem acentos)
PALAVRAS_CHAVE_ATA = [
    "ata",
    "ata de audiencia",
    "ata audiencia",
    "ata sem sentenca",
    "termo de audiencia",
    "termo audiencia",
    "assentada",
]

# Termos que EXCLUEM um documento de ser ata (têm prioridade)
PALAVRAS_IGNORAR = [
    "decisao",
    "despacho",
    "certidao",
    "peticao",
    "mandado",
    "oficio",
]

# ─── Mapeamento de tribunais para scrapers ────────────────────────────────────
# Chave: identificador do tribunal (string uppercase)
# Valor: caminho dotted do módulo + nome da classe
TRIBUNAIS_SUPORTADOS = {
    "TJMG": "tribunais.tjmg_pje.TJMGPJeScraper",
    # Futuros conectores — adicione aqui conforme implementação:
    # "TJSP": "tribunais.tjsp_esaj.TJSPEsajScraper",
    # "TRF5": "tribunais.trf5_pje.TRF5PJeScraper",
    # "TJSE": "tribunais.tjse_pje.TJSEPJeScraper",
}

# ─── Comportamento do loop ────────────────────────────────────────────────────
LOOP_INTERVAL_SECONDS = 5  # pausa entre ciclos no modo --loop

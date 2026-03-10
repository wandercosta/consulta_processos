"""
Configurações globais do sistema de automação de processos jurídicos.

Centraliza todas as constantes e parâmetros configuráveis.
Lê variáveis sensíveis do arquivo .env na raiz do projeto —
copie .env.example para .env e ajuste os valores antes de executar.
"""

import os


# ─── Loader de .env (sem dependências externas) ───────────────────────────────

def _carregar_env(path: str) -> dict:
    """Lê o arquivo .env e retorna um dicionário com as variáveis."""
    if not os.path.isfile(path):
        return {}
    resultado = {}
    with open(path, encoding="utf-8") as f:
        for linha in f:
            linha = linha.strip()
            if not linha or linha.startswith("#") or "=" not in linha:
                continue
            chave, _, valor = linha.partition("=")
            chave = chave.strip()
            valor = valor.strip()
            # Remove aspas ao redor do valor (duplas ou simples)
            if len(valor) >= 2 and valor[0] == valor[-1] and valor[0] in ('"', "'"):
                valor = valor[1:-1]
            if chave:
                resultado[chave] = valor
    return resultado


def _env(chave: str, padrao: str = "") -> str:
    """
    Retorna o valor de uma variável de ambiente.
    Prioridade: variável de sistema > .env > padrão.
    """
    return os.environ.get(chave) or _env_data.get(chave) or padrao


# Carrega o .env da raiz do projeto (um nível acima de python/)
_ENV_FILE = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), ".env")
_env_data = _carregar_env(_ENV_FILE)


# ─── Diretório base do projeto Python ────────────────────────────────────────
BASE_DIR = os.path.dirname(os.path.abspath(__file__))

# ─── API Local PHP ────────────────────────────────────────────────────────────
API_BASE_URL = _env("API_BASE_URL", "http://localhost/processos_api/api")
API_TOKEN    = _env("API_TOKEN",    "CLAUDE_AUTOMACAO_123")
API_TIMEOUT  = 30  # segundos

# ─── Selenium ─────────────────────────────────────────────────────────────────
SELENIUM_EXPLICIT_WAIT = 10   # segundos de espera para elementos
PAGE_LOAD_TIMEOUT      = 30   # segundos para carregamento de página

# ─── Diretórios ───────────────────────────────────────────────────────────────
DEFAULT_DOWNLOAD_DIR = os.path.join(BASE_DIR, "downloads")
LOG_DIR  = os.path.join(BASE_DIR, "logs")
LOG_FILE = os.path.join(LOG_DIR, "automacao.log")

# ─── Logging ──────────────────────────────────────────────────────────────────
LOG_FORMAT      = "[%(asctime)s] %(levelname)-7s %(message)s"
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

# processos_api — Módulo Python de Automação

Sistema de automação para consulta de processos jurídicos em tribunais brasileiros e download de atas de audiência, integrado à API PHP local do projeto `processos_api`.

---

## Estrutura

```
python/
├── main.py                  # Ponto de entrada e orquestrador do fluxo
├── config.py                # Configurações globais (URLs, tokens, palavras-chave)
├── requirements.txt         # Dependências Python
│
├── core/
│   ├── api_client.py        # Comunicação com a API PHP local
│   ├── downloader.py        # Download de PDFs e HTMLs com sessão autenticada
│   ├── logger_setup.py      # Configuração de logging (console + arquivo rotativo)
│   ├── driver_factory.py    # Factory do WebDriver Selenium (Chrome)
│   └── utils.py             # Funções utilitárias (classificação, relatório, etc.)
│
├── tribunais/
│   ├── base_scraper.py      # Classe abstrata base para todos os tribunais
│   └── tjmg_pje.py          # Implementação concreta — PJe TJMG
│
├── models/
│   └── documento.py         # Dataclass padronizada para documentos encontrados
│
├── logs/
│   └── automacao.log        # Log rotativo (criado automaticamente)
│
└── downloads/
    └── TJMG/                # PDFs e HTMLs baixados (organizados por tribunal)
```

---

## Instalação

### Pré-requisitos

- Python 3.10+
- Google Chrome instalado
- WAMP64 rodando com a API PHP ativa em `http://localhost/processos_api/api`

### Instalando dependências

```bash
cd E:\wamp64\www\processos_api\python
pip install -r requirements.txt
```

O `webdriver-manager` baixa automaticamente o ChromeDriver compatível com seu Chrome.

---

## Configuração

Edite `config.py` para ajustar:

| Parâmetro | Padrão | Descrição |
|---|---|---|
| `API_BASE_URL` | `http://localhost/processos_api/api` | URL base da API PHP |
| `API_TOKEN` | `CLAUDE_AUTOMACAO_123` | Token Bearer de autenticação |
| `DEFAULT_DOWNLOAD_DIR` | `python/downloads/` | Pasta de destino dos arquivos |
| `LOOP_INTERVAL_SECONDS` | `5` | Pausa entre ciclos no modo `--loop` |

> **Atenção:** Se a API PHP usa roteamento com `?endpoint=...` (ex: `index.php?endpoint=processos_pendentes`), ajuste `API_BASE_URL` para `http://localhost/processos_api` e configure o Apache/WAMP com mod_rewrite para URLs limpas.

---

## Uso

### Processar 1 processo (modo padrão)

```bash
python main.py
```

### Processar fila completa (modo loop)

```bash
python main.py --loop
```

### Modo servidor — sem janela do browser

```bash
python main.py --headless --loop
```

### Diagnóstico detalhado

```bash
python main.py --log-level DEBUG
```

### Pasta de download personalizada

```bash
python main.py --download-dir "D:\atas_processos"
```

---

## Argumentos disponíveis

| Argumento | Descrição |
|---|---|
| `--loop` | Processa todos os processos pendentes em sequência |
| `--headless` | Chrome sem interface gráfica (ideal para servidores) |
| `--download-dir DIR` | Pasta de destino dos downloads |
| `--log-level LEVEL` | DEBUG, INFO, WARNING ou ERROR (padrão: INFO) |

---

## Fluxo de execução

```
1. GET /processos_pendentes       → busca próximo processo da fila
2. POST /registrar_consulta       → marca como CONSULTANDO
3. POST /logs                     → registra início
4. Selenium → abre portal TJMG   → pesquisa o processo
5. Selenium → abre página de detalhe
6. Mapeia documentos juntados    → classifica atas por palavras-chave
7. Download das atas             → PDF (via idBin) ou HTML (via onclick)
8. POST /registrar_ata           → registra sucesso com nomes dos arquivos
9. Relatório final no console
```

---

## Saída esperada

```
[2026-03-06 10:00:00] INFO    Processo recebido: 5000213-62.2026.8.13.0521 | Tribunal: TJMG
[2026-03-06 10:00:03] INFO    [TJMG] Resultado encontrado no PJe
[2026-03-06 10:00:07] INFO    [TJMG] Página de detalhe aberta com sucesso
[2026-03-06 10:00:09] INFO    [TJMG] Ata encontrada: Ata de audiência 01/2026
[2026-03-06 10:00:12] INFO    Download OK: TJMG_5000213-62.2026.8.13.0521_ATA_1_06032026.pdf (511 KB)
[2026-03-06 10:00:16] INFO    Registro enviado à API local

══════════════════════════════════════════════════
RELATÓRIO FINAL
Processo   : 5000213-62.2026.8.13.0521
Tribunal   : TJMG
Encontradas: 1
Baixadas   : 1
Status     : SUCESSO
══════════════════════════════════════════════════
```

---

## Adicionando suporte a novos tribunais

1. Crie `tribunais/novo_tribunal.py` herdando `BaseScraper`:

```python
from tribunais.base_scraper import BaseScraper

class TJSPEsajScraper(BaseScraper):
    @property
    def nome_tribunal(self) -> str:
        return "TJSP"

    @property
    def url_consulta(self) -> str:
        return "https://esaj.tjsp.jus.br/..."

    def abrir_consulta(self) -> bool: ...
    def pesquisar_processo(self, numero_processo: str) -> bool: ...
    def abrir_detalhe(self, numero_processo: str) -> bool: ...
    def mapear_documentos(self, numero_processo: str) -> list: ...
    def detectar_bloqueio(self) -> str: ...
```

2. Registre em `config.py`:

```python
TRIBUNAIS_SUPORTADOS = {
    "TJMG": "tribunais.tjmg_pje.TJMGPJeScraper",
    "TJSP": "tribunais.tjsp_esaj.TJSPEsajScraper",  # novo
}
```

3. Popule a coluna `tribunal` no banco com a nova sigla.

A factory em `main.py` instancia o scraper automaticamente.

---

## Palavras-chave configuradas

**Identificam como ATA:**
- ata, ata de audiência, ata audiencia, ata sem sentença, termo de audiência, termo audiencia, assentada

**Excluem da classificação:**
- decisão, despacho, certidão, petição, mandado, ofício

Todas as comparações são **case-insensitive** e **ignoram acentos**.

---

## Logs

- Console: exibição em tempo real
- Arquivo: `logs/automacao.log` (rotativo, máx 5 MB × 3 backups)
- API remota: cada evento relevante é enviado via `POST /logs`

---

## Integração com a API PHP

Todos os endpoints usam:
```
Authorization: Bearer CLAUDE_AUTOMACAO_123
Content-Type: application/json
```

| Método | Endpoint | Descrição |
|---|---|---|
| GET | `/processos_pendentes` | Lista processos aguardando consulta |
| POST | `/registrar_consulta` | Marca processo como CONSULTANDO |
| POST | `/logs` | Persiste log no banco |
| POST | `/registrar_ata` | Registra atas baixadas |
| POST | `/registrar_erro` | Registra falha crítica |
| GET | `/status_processo?id=N` | Consulta status atual |

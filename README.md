# Consulta Processos

Sistema para consulta automatizada de processos jurídicos nos tribunais brasileiros. Combina uma **API REST em PHP**, um **painel administrativo web** e um **robô Python com Selenium** que acessa os portais dos tribunais e coleta as atas de audiência.

---

## Visão geral

```
┌─────────────────────┐        REST API         ┌──────────────────────┐
│  Painel (PHP/Web)   │ ◄─────────────────────► │  Robô Python         │
│  Cadastro / Gestão  │                          │  Selenium + Chrome   │
└─────────────────────┘                          └──────────────────────┘
           │                                                │
           └────────────────────┬───────────────────────────┘
                                ▼
                     ┌──────────────────┐
                     │  MySQL Database  │
                     │  consulta_       │
                     │  processos       │
                     └──────────────────┘
                                │
                      Webhook (HTTP POST)
                                │
                                ▼
                     ┌──────────────────┐
                     │  Sistema externo │
                     │  (integração)    │
                     └──────────────────┘
```

**Fluxo principal:**

1. Processo é cadastrado via painel ou importação de planilha → status `PENDENTE`
2. Robô busca processos `PENDENTE` pela API → marca como `CONSULTANDO`
3. Robô acessa o portal do tribunal com Selenium, localiza a ata de audiência e faz download
4. Robô registra o resultado via API → status `FINALIZADO COM ATA`, `FINALIZADO SEM ATA`, `NÃO COMPATÍVEL` ou `ERRO`
5. API dispara webhook para URL configurada com o payload do processo

---

## Estrutura do projeto

```
processos_api/
├── api/                          # API REST PHP
│   ├── config/
│   │   ├── Auth.php              # Validação do token Bearer
│   │   ├── Database.php          # Conexão PDO
│   │   └── Env.php               # Loader de .env
│   ├── Domain/
│   │   ├── Arquivo/
│   │   ├── Processo/
│   │   └── Robot/
│   ├── Http/Controllers/
│   │   ├── ArquivoController.php
│   │   ├── ProcessoController.php
│   │   └── RobotController.php
│   ├── Infrastructure/
│   │   ├── ArquivoRepositoryPDO.php
│   │   ├── ProcessoRepositoryPDO.php
│   │   ├── RobotRepositoryPDO.php
│   │   └── WebhookService.php    # Disparo e log de webhooks
│   └── index.php                 # Roteador da API
│
├── painel/                       # Interface administrativa
│   ├── Controllers/
│   │   ├── ArquivoController.php
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── ImportController.php  # Importação via planilha
│   │   ├── ProcessoController.php
│   │   ├── RobotController.php
│   │   └── WebhookController.php
│   ├── Models/
│   │   ├── ArquivoModel.php
│   │   ├── ProcessoModel.php
│   │   ├── RobotModel.php
│   │   └── WebhookModel.php
│   ├── Views/
│   │   ├── dashboard/
│   │   ├── docs/
│   │   ├── layout/
│   │   ├── processos/            # index, detalhe, cadastrar, importar
│   │   └── webhook/
│   ├── lib/
│   │   └── XlsxReader.php        # Parser XLSX puro PHP
│   ├── migrations/               # Migrações incrementais (001–012)
│   │   └── index.php             # Runner web de migrações
│   ├── config/config.php
│   └── index.php                 # Roteador do painel
│
├── python/                       # Robô de automação
│   ├── core/
│   │   ├── api_client.py         # Cliente da API REST
│   │   └── base_scraper.py       # Classe base dos scrapers
│   ├── models/
│   │   └── documento.py
│   ├── tribunais/
│   │   └── tjmg_pje.py           # Scraper TJMG / PJe
│   ├── config.py                 # Configurações e constantes
│   ├── daemon.py                 # Modo daemon (loop contínuo)
│   ├── main.py                   # Ponto de entrada principal
│   └── requirements.txt
│
├── uploads/                      # Arquivos baixados (ATAs)
├── .env.example                  # Variáveis de ambiente (copie para .env)
└── README.md
```

---

## Pré-requisitos

### PHP / API / Painel
- PHP 8.1+
- MySQL 5.7+ ou MariaDB 10.4+
- Extensões PHP: `pdo_mysql`, `zip`, `simplexml`, `curl`
- Servidor web: Apache (WAMP, XAMPP) ou Nginx

### Python / Robô
- Python 3.10+
- Google Chrome instalado
- Pacotes: `selenium`, `webdriver-manager`, `requests`

---

## Instalação

### 1. Clone e configure o ambiente

```bash
git clone https://github.com/wandercosta/consulta_processos.git
cd consulta_processos
cp .env.example .env
```

Edite o `.env` com suas configurações:

```env
# Banco de dados
DB_HOST=localhost
DB_NAME=consulta_processos
DB_USER=root
DB_PASS=

# Caminho base na URL (ex: /processos_api para WAMP local; vazio para raiz)
APP_BASE_PATH=/processos_api

# Token de autenticação da API
API_TOKEN=CLAUDE_AUTOMACAO_123

# URL da API (usada pelo robô Python)
API_BASE_URL=http://localhost/processos_api/api
```

### 2. Crie o banco de dados

```sql
CREATE DATABASE consulta_processos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Execute as migrações

Acesse no navegador:

```
http://localhost/processos_api/painel/migrations/
```

Clique em **"Executar todas as pendentes"**. As 12 migrações criarão todas as tabelas necessárias.

### 4. Acesse o painel

```
http://localhost/processos_api/painel/
```

Senha padrão: `admin123` (altere em `painel/config/config.php` → `PAINEL_SENHA`)

### 5. Instale as dependências Python

```bash
cd python
pip install -r requirements.txt
```

---

## Uso

### Painel administrativo

| Seção | Função |
|---|---|
| **Dashboard** | Visão geral com contadores e últimos processos |
| **Processos** | Listagem com filtros, cancelar e recolocar na fila |
| **Cadastrar** | Adicionar processo individualmente |
| **Importar Planilha** | Upload de `.xlsx` ou `.csv` para cadastro em lote |
| **Arquivos / ATAs** | Download e gestão das atas coletadas |
| **Robô** | Liga/desliga o robô, monitora status e heartbeat |
| **Webhooks** | Configura URL, visualiza histórico e reenvia |
| **Documentação** | Referência completa da API e banco de dados |

### Importação via planilha

Envie um `.xlsx` ou `.csv` com as colunas:

| Coluna | Obrigatório | Exemplo |
|---|---|---|
| `processo` | ✅ | `5003854-46.2025.8.13.0407` |
| `data` | — | `22/04/2025` |
| `uf` | ✅ | `MG` |
| `idapi` | — | `ORD-2025-001` |

A ordem das colunas é livre; o cabeçalho é detectado automaticamente. Duplicatas são ignoradas.

### Robô Python

```bash
cd python

# Processa um ciclo e sai
python main.py

# Modo loop contínuo (daemon)
python main.py --loop

# Ou use o script .bat (Windows)
run_headless_loop.bat
```

---

## API REST

**Base URL:** `http://localhost/processos_api/api/index.php`
**Autenticação:** `Authorization: Bearer CLAUDE_AUTOMACAO_123`

### Endpoints principais

| Método | Endpoint | Descrição |
|---|---|---|
| `GET` | `?endpoint=processos_pendentes` | Lista processos na fila |
| `POST` | `?endpoint=cadastrar_processo` | Cadastra novo processo |
| `GET` | `?endpoint=listar_processos` | Lista com filtros e paginação |
| `GET` | `?endpoint=status_processo&id=X` | Status de um processo |
| `POST` | `?endpoint=registrar_consulta` | Marca como CONSULTANDO |
| `POST` | `?endpoint=registrar_ata` | Finaliza COM ATA |
| `POST` | `?endpoint=registrar_sem_ata` | Finaliza SEM ATA |
| `POST` | `?endpoint=registrar_erro` | Registra ERRO |
| `POST` | `?endpoint=registrar_nao_compativel` | Marca como NÃO COMPATÍVEL |
| `POST` | `?endpoint=logs` | Adiciona log de execução |
| `GET` | `?endpoint=download_arquivo_id&id=X` | Download de arquivo por ID |

**Exemplo — cadastrar processo:**

```bash
curl -X POST http://localhost/processos_api/api/index.php?endpoint=cadastrar_processo \
  -H "Authorization: Bearer CLAUDE_AUTOMACAO_123" \
  -H "Content-Type: application/json" \
  -d '{
    "numero_processo": "5003854-46.2025.8.13.0407",
    "tribunal": "MG",
    "data_ato": "2025-04-22",
    "cod_api": "ORD-2025-001"
  }'
```

---

## Webhook

Configure a URL de destino em **Painel → Webhooks → Configuração**.

Ao finalizar uma busca, o sistema envia um `POST` com:

```json
{
  "evento": "FINALIZADO COM ATA",
  "id_integracao": "ORD-2025-001",
  "numero_processo": "5003854-46.2025.8.13.0407",
  "status": "FINALIZADO COM ATA",
  "tribunal": "MG",
  "tipo_sistema": "PJE",
  "qtd_atas": 1,
  "data_consulta": "2025-03-12 10:30:00",
  "arquivos": [
    {
      "id": 42,
      "nome": "ata_audiencia.pdf",
      "formato": "PDF",
      "tamanho_bytes": 102400,
      "url": "https://meuservidor.com/processos_api/api/?endpoint=download_arquivo_id&id=42"
    }
  ]
}
```

Header opcional: `X-Webhook-Secret` para verificação de autenticidade.

---

## Classificação automática de tipo de sistema

O tipo de sistema é inferido automaticamente pelo **1º dígito** do número do processo:

| UF | Dígito | Sistema |
|---|---|---|
| MG | `5` | PJe |
| MG | `0` ou `1` | EPROC |
| MG | `2` | PROCON |
| MG | outros | DESCONHECIDO |

> Regras são por UF. Outros estados terão suas próprias tabelas conforme implementação.

---

## Status dos processos

| Status | Descrição |
|---|---|
| `PENDENTE` | Aguardando consulta |
| `CONSULTANDO` | Robô em execução |
| `FINALIZADO COM ATA` | Ata localizada e baixada |
| `FINALIZADO SEM ATA` | Consultado, sem ata disponível |
| `NÃO COMPATÍVEL` | Sistema do tribunal não suportado |
| `ERRO` | Falha durante a consulta |
| `CANCELADO` | Cancelado manualmente |

Processos `FINALIZADO SEM ATA` são recolocados na fila automaticamente até **10 tentativas**, com intervalo mínimo de **60 minutos** entre cada consulta.

---

## Banco de dados

| Tabela | Descrição |
|---|---|
| `processos` | Registro principal de cada processo |
| `processos_logs` | Histórico de execução por processo |
| `processos_arquivos` | Arquivos baixados (ATAs) |
| `robot_config` | Configuração e heartbeat do robô |
| `webhook_config` | URL e secret do webhook |
| `webhook_logs` | Histórico de disparos com payload e resposta |
| `schema_migrations` | Controle de migrações aplicadas |

---

## Tribunais suportados

| UF | Tribunal | Sistema | Status |
|---|---|---|---|
| MG | TJMG | PJe | ✅ Implementado |
| SP | TJSP | eSAJ | 🚧 Planejado |
| SE | TJSE | PJe | 🚧 Planejado |

Para adicionar um novo tribunal, crie `python/tribunais/<uf>_<sistema>.py` herdando de `BaseScraper` e registre em `python/config.py` → `TRIBUNAIS_SUPORTADOS`.

---

## Licença

Uso interno. Todos os direitos reservados.

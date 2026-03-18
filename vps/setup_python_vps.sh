#!/bin/bash
# ============================================================
#  setup_python_vps.sh
#  Configura o ambiente Python na VPS para rodar o daemon
#  de automação de processos jurídicos.
#
#  Uso:
#    chmod +x setup_python_vps.sh
#    sudo bash setup_python_vps.sh
#
#  O script detecta automaticamente:
#    - Caminho do projeto (baseado na localização do script)
#    - Usuário que deve rodar o serviço
#    - Versão do Python disponível
# ============================================================

set -e

# ── Cores para output ────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # sem cor

ok()   { echo -e "     ${GREEN}✔${NC} $1"; }
warn() { echo -e "     ${YELLOW}⚠${NC}  $1"; }
erro() { echo -e "     ${RED}✘${NC} $1"; }
info() { echo -e "     ${BLUE}→${NC} $1"; }

# ── Verificação: deve ser root ───────────────────────────────
if [ "$EUID" -ne 0 ]; then
    erro "Execute como root: sudo bash setup_python_vps.sh"
    exit 1
fi

# ── Detecção automática de caminhos ─────────────────────────
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJETO_DIR="$(dirname "$SCRIPT_DIR")"   # um nível acima de vps/
PYTHON_DIR="$PROJETO_DIR/python"
ENV_FILE="$PROJETO_DIR/.env"
SERVICE_FILE="/etc/systemd/system/processos-daemon.service"

# ── Detecção do usuário do serviço ──────────────────────────
# Usa o dono atual da pasta do projeto (quem fez o upload via FTP)
SERVICE_USER=$(stat -c '%U' "$PROJETO_DIR" 2>/dev/null || echo "root")
# Se o dono for root, mantém root; caso contrário usa o dono real
if [ "$SERVICE_USER" = "root" ] || [ "$SERVICE_USER" = "UNKNOWN" ]; then
    SERVICE_USER="root"
fi

# ── Detecção do Python ───────────────────────────────────────
PYTHON_BIN=""
for py in python3.12 python3.11 python3.10 python3 python; do
    if command -v "$py" &>/dev/null; then
        PYTHON_BIN=$(command -v "$py")
        break
    fi
done

echo ""
echo "======================================================="
echo " Automação de Processos — Setup VPS"
echo "======================================================="
echo ""
info "Projeto:  $PROJETO_DIR"
info "Python:   ${PYTHON_BIN:-NÃO ENCONTRADO}"
info "Usuário:  $SERVICE_USER"
echo ""

# ── Valida que a pasta python/ existe ───────────────────────
if [ ! -d "$PYTHON_DIR" ]; then
    erro "Pasta python/ não encontrada em: $PYTHON_DIR"
    erro "Verifique se o upload do projeto foi feito corretamente."
    exit 1
fi

if [ ! -f "$PYTHON_DIR/requirements.txt" ]; then
    erro "requirements.txt não encontrado em: $PYTHON_DIR"
    exit 1
fi

# ── 1. Dependências do sistema ──────────────────────────────
echo "[1/6] Instalando dependências do sistema..."
apt-get update -qq
apt-get install -y python3 python3-pip wget curl gnupg unzip 2>/dev/null
ok "Dependências instaladas"

# ── 2. Google Chrome ────────────────────────────────────────
echo ""
echo "[2/6] Verificando Google Chrome..."
if ! command -v google-chrome &>/dev/null; then
    info "Baixando Chrome..."
    wget -q https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb -O /tmp/chrome.deb
    # Instala dependências do Chrome automaticamente
    apt-get install -y /tmp/chrome.deb 2>/dev/null || apt-get install -f -y 2>/dev/null
    rm -f /tmp/chrome.deb
    if command -v google-chrome &>/dev/null; then
        ok "Chrome instalado: $(google-chrome --version)"
    else
        erro "Falha ao instalar o Chrome. Tente manualmente:"
        erro "  wget https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb"
        erro "  apt-get install -y ./google-chrome-stable_current_amd64.deb"
        exit 1
    fi
else
    ok "Chrome já instalado: $(google-chrome --version)"
fi

# ── 3. Dependências Python ──────────────────────────────────
echo ""
echo "[3/6] Instalando dependências Python..."
if [ -z "$PYTHON_BIN" ]; then
    erro "Python não encontrado. Instale com: apt-get install -y python3 python3-pip"
    exit 1
fi
$PYTHON_BIN -m pip install --quiet -r "$PYTHON_DIR/requirements.txt"
ok "Libs Python instaladas ($(pip3 show selenium 2>/dev/null | grep Version || echo 'ok'))"

# ── 4. Permissões das pastas ─────────────────────────────────
echo ""
echo "[4/6] Ajustando permissões..."

# Cria pastas necessárias se não existirem
mkdir -p "$PYTHON_DIR/logs"
mkdir -p "$PYTHON_DIR/downloads"

# Corrige dono e permissão
chown -R "$SERVICE_USER":"$SERVICE_USER" "$PYTHON_DIR/logs"
chown -R "$SERVICE_USER":"$SERVICE_USER" "$PYTHON_DIR/downloads"
chmod -R 755 "$PYTHON_DIR/logs"
chmod -R 755 "$PYTHON_DIR/downloads"
ok "Permissões de logs/ e downloads/ ajustadas para $SERVICE_USER"

# Garante que o HOME do usuário do serviço existe (evita PermissionError do webdriver-manager)
USER_HOME=$(getent passwd "$SERVICE_USER" | cut -d: -f6 2>/dev/null || echo "/root")
if [ ! -w "$USER_HOME" ]; then
    warn "Home do usuário $SERVICE_USER ($USER_HOME) não tem escrita — usando /tmp como HOME no serviço"
    EXTRA_ENV="Environment=HOME=/tmp"
else
    EXTRA_ENV="Environment=HOME=$USER_HOME"
fi
ok "HOME configurado: $(echo $EXTRA_ENV | cut -d= -f2-)"

# ── 5. Arquivo .env ─────────────────────────────────────────
echo ""
echo "[5/6] Configurando .env..."
if [ -f "$ENV_FILE" ]; then
    warn ".env já existe — mantendo o atual."
    info "Para atualizar: nano $ENV_FILE"
else
    if [ -f "$SCRIPT_DIR/.env.producao" ]; then
        cp "$SCRIPT_DIR/.env.producao" "$ENV_FILE"
        ok ".env criado em $ENV_FILE"
        warn "ATENÇÃO: edite $ENV_FILE e confirme DB_USER, DB_PASS e PAINEL_SENHA"
    else
        erro ".env.producao não encontrado em $SCRIPT_DIR"
        erro "Crie o arquivo .env manualmente em $ENV_FILE"
        exit 1
    fi
fi

# ── 6. Serviço systemd ──────────────────────────────────────
echo ""
echo "[6/6] Configurando serviço systemd..."

cat > "$SERVICE_FILE" <<EOF
[Unit]
Description=Daemon de Automação de Processos Jurídicos
After=network.target

[Service]
Type=simple
User=$SERVICE_USER
WorkingDirectory=$PYTHON_DIR
ExecStart=$PYTHON_BIN $PYTHON_DIR/daemon.py --headless
Restart=on-failure
RestartSec=15
StandardOutput=journal
StandardError=journal
Environment=PYTHONUNBUFFERED=1
$EXTRA_ENV

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable processos-daemon
systemctl restart processos-daemon
sleep 2  # aguarda inicialização

ok "Serviço systemd configurado e iniciado"

# ── Resultado final ──────────────────────────────────────────
echo ""
echo "======================================================="
echo -e " ${GREEN}Setup concluído!${NC}"
echo "======================================================="
echo ""
echo " Configuração aplicada:"
info "Projeto:  $PROJETO_DIR"
info "Python:   $PYTHON_BIN"
info "Usuário:  $SERVICE_USER"
info "Serviço:  $SERVICE_FILE"
echo ""
echo " Status do daemon:"
systemctl status processos-daemon --no-pager
echo ""
echo " Comandos úteis:"
echo "   Ver logs:    journalctl -u processos-daemon -f"
echo "   Parar:       systemctl stop processos-daemon"
echo "   Reiniciar:   systemctl restart processos-daemon"
echo "   Desativar:   systemctl disable processos-daemon"
echo ""
echo " Acesse o painel para ativar o robô:"
echo "   http://processos.auradevcode.com.br/painel/?page=robot"
echo ""

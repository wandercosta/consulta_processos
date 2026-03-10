"""
Daemon de automação: loop contínuo controlado pelo painel web.

O daemon verifica a cada CICLO_SEGUNDOS se o robô está ativo (via API/BD).
Quando ativo, processa os processos pendentes um a um.
Garante que nunca há duas execuções simultâneas.

Uso:
    python daemon.py                   # roda com Chrome visível
    python daemon.py --headless        # roda sem janela (recomendado para produção)
    python daemon.py --log-level DEBUG # log detalhado

Para parar: Ctrl+C  (aguarda o ciclo atual terminar antes de encerrar)
"""

import sys
import os

_script_dir = os.path.dirname(os.path.abspath(__file__))
if _script_dir not in sys.path:
    sys.path.insert(0, _script_dir)

# Força UTF-8 no stdout/stderr
if hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
if hasattr(sys.stderr, "reconfigure"):
    sys.stderr.reconfigure(encoding="utf-8", errors="replace")

import argparse
import logging
import signal
import time
from datetime import datetime

import config
from core.api_client import APIClient
from core.downloader import Downloader
from core.logger_setup import configurar_logger
from main import processar_um

# ── Constantes ────────────────────────────────────────────────────────────────

CICLO_SEGUNDOS = 20          # intervalo entre verificações quando aguardando
SEP = "─" * 60

# ── Sinal de encerramento ─────────────────────────────────────────────────────

_parar = False


def _handle_signal(sig, frame) -> None:  # noqa: ANN001
    global _parar
    _parar = True


signal.signal(signal.SIGINT,  _handle_signal)
signal.signal(signal.SIGTERM, _handle_signal)


# ── Loop principal ────────────────────────────────────────────────────────────

def executar_daemon(headless: bool) -> None:
    global _parar

    logger = logging.getLogger("automacao")
    pid    = os.getpid()

    api        = APIClient(base_url=config.API_BASE_URL, token=config.API_TOKEN, timeout=config.API_TIMEOUT)
    downloader = Downloader(diretorio_base=config.DEFAULT_DOWNLOAD_DIR)

    logger.info("=" * 60)
    logger.info(f"Daemon iniciado  |  PID={pid}  |  Headless={headless}")
    logger.info(f"Ciclo de verificação: {CICLO_SEGUNDOS}s")
    logger.info(f"API: {config.API_BASE_URL}")
    logger.info("Aguardando ativação no painel — acesse ?page=robot")
    logger.info("=" * 60)

    while not _parar:
        try:
            # ── 1. Verifica se o painel ativou o robô ─────────────────────────
            cfg_robot = api.robot_status()

            if not isinstance(cfg_robot, dict):
                logger.warning("Não foi possível obter robot_status da API. Tentando em breve...")
                _aguardar(CICLO_SEGUNDOS)
                continue

            ativo = bool(int(cfg_robot.get("ativo", 0)))

            if not ativo:
                # Modo standby: apenas heartbeat e dorme
                api.robot_heartbeat("parado", pid, "Aguardando ativação no painel")
                logger.debug("Standby — robô desativado no painel")
                _aguardar(CICLO_SEGUNDOS)
                continue

            # ── 2. Robô ativo: verifica fila ─────────────────────────────────
            api.robot_heartbeat("verificando", pid, "Verificando fila de processos")
            pendentes = api.buscar_processos_pendentes()

            if not pendentes:
                api.robot_heartbeat("aguardando", pid, "Fila vazia — aguardando novos processos")
                logger.debug("Ativo — fila vazia")
                _aguardar(CICLO_SEGUNDOS)
                continue

            # ── 3. Há processos: inicia automação ────────────────────────────
            qtd = len(pendentes)
            proximo = pendentes[0].get("numero_processo", "???")
            logger.info(SEP)
            logger.info(f"Daemon: {qtd} processo(s) na fila — iniciando: {proximo}")
            logger.info(SEP)

            api.robot_heartbeat(
                "executando", pid,
                f"Processando {proximo} ({qtd} na fila)"
            )

            try:
                processar_um(api, downloader, headless=headless, download_dir=config.DEFAULT_DOWNLOAD_DIR)
            except Exception as e:
                logger.error(f"Daemon: erro durante processar_um: {e}")

            fim = datetime.now().strftime("%H:%M:%S")
            api.robot_heartbeat("aguardando", pid, f"Ciclo concluído às {fim}")

        except KeyboardInterrupt:
            break
        except Exception as e:
            logger.error(f"Daemon: erro inesperado no ciclo: {e}")
            try:
                api.robot_heartbeat("erro", pid, str(e)[:255])
            except Exception:
                pass
            _aguardar(CICLO_SEGUNDOS)

        # Pausa antes do próximo ciclo (interrompível pelo sinal)
        if not _parar:
            _aguardar(CICLO_SEGUNDOS)

    # ── Encerramento ──────────────────────────────────────────────────────────
    logger.info(f"Daemon encerrado  |  PID={pid}")
    try:
        api.robot_heartbeat("parado", pid, "Daemon encerrado pelo operador")
    except Exception:
        pass


def _aguardar(segundos: int) -> None:
    """Aguarda 'segundos' usando sleep de 1s para responder rapidamente ao Ctrl+C."""
    global _parar
    for _ in range(segundos):
        if _parar:
            break
        time.sleep(1)


# ── Entry point ───────────────────────────────────────────────────────────────

def _parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Daemon de automação de processos jurídicos",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Exemplos:
  python daemon.py                   Roda com Chrome visível
  python daemon.py --headless        Roda sem janela (modo servidor)
  python daemon.py --log-level DEBUG Log detalhado
        """,
    )
    parser.add_argument(
        "--headless",
        action="store_true",
        help="Executar Chrome sem interface gráfica (recomendado)",
    )
    parser.add_argument(
        "--log-level",
        type=str,
        default="INFO",
        choices=["DEBUG", "INFO", "WARNING", "ERROR"],
        help="Nível de detalhe dos logs (padrão: INFO)",
    )
    return parser.parse_args()


def main() -> None:
    args = _parse_args()
    configurar_logger(nivel=args.log_level)
    executar_daemon(headless=args.headless)


if __name__ == "__main__":
    main()

"""
Ponto de entrada do sistema de automação de processos jurídicos brasileiros.

Uso:
    python main.py [--loop] [--headless] [--download-dir DIR] [--log-level LEVEL]

Exemplos:
    python main.py                          # Processa 1 processo com interface
    python main.py --loop                   # Processa todos os pendentes
    python main.py --headless --loop        # Modo servidor (sem janela)
    python main.py --log-level DEBUG        # Log detalhado para diagnóstico
"""

# Garante que a pasta do script esteja sempre no sys.path,
# independente do diretório de trabalho onde o script é invocado.
import sys as _sys
import os as _os
_script_dir = _os.path.dirname(_os.path.abspath(__file__))
if _script_dir not in _sys.path:
    _sys.path.insert(0, _script_dir)

# Força UTF-8 no stdout/stderr para evitar UnicodeEncodeError no Windows (cp1252)
if hasattr(_sys.stdout, "reconfigure"):
    _sys.stdout.reconfigure(encoding="utf-8", errors="replace")
if hasattr(_sys.stderr, "reconfigure"):
    _sys.stderr.reconfigure(encoding="utf-8", errors="replace")

import argparse
import importlib
import logging
import sys
import time
from datetime import date as Date
from typing import List, Optional

import config
from core.api_client import APIClient
from core.downloader import Downloader
from core.driver_factory import criar_driver
from core.logger_setup import configurar_logger
from core.utils import formatar_numero_processo, imprimir_relatorio
from models.documento import Documento
from tribunais.base_scraper import BaseScraper


# ── Factory de scrapers ───────────────────────────────────────────────────────

def carregar_scraper(tribunal: str, driver, download_dir: str) -> Optional[BaseScraper]:
    """
    Instancia o scraper correto para o tribunal informado via importação dinâmica.

    Para adicionar suporte a um novo tribunal:
      1. Crie tribunais/{sigla}_pje.py (ou outro sufixo)
      2. Adicione a entrada em config.TRIBUNAIS_SUPORTADOS

    Args:
        tribunal:     UF do estado em maiúsculas (ex: "MG").
        driver:       Instância do WebDriver já criada.
        download_dir: Diretório de destino dos downloads.

    Returns:
        Instância do scraper, ou None se o tribunal não for suportado/falhar.
    """
    caminho = config.TRIBUNAIS_SUPORTADOS.get(tribunal.upper())
    if not caminho:
        logging.getLogger("automacao").error(
            f"Tribunal '{tribunal}' não suportado. "
            f"Suportados: {list(config.TRIBUNAIS_SUPORTADOS.keys())}"
        )
        return None

    try:
        modulo_path, classe_nome = caminho.rsplit(".", 1)
        modulo = importlib.import_module(modulo_path)
        classe = getattr(modulo, classe_nome)
        return classe(driver=driver, download_dir=download_dir)
    except Exception as e:
        logging.getLogger("automacao").error(
            f"Falha ao instanciar scraper para {tribunal}: {e}"
        )
        return None


# ── Ciclo principal ───────────────────────────────────────────────────────────

def processar_um(
    api: APIClient,
    downloader: Downloader,
    headless: bool,
    download_dir: str,
) -> bool:
    """
    Executa o ciclo completo para um único processo:
    busca → seleciona scraper → scraping → download → registro.

    Returns:
        True  → processo encontrado e tratado (com ou sem atas).
        False → fila vazia, nada a processar.
    """
    logger = logging.getLogger("automacao")
    SEP = "─" * 60

    # ── ETAPA 0: Buscar processo pendente ─────────────────────────────────────
    pendentes = api.buscar_processos_pendentes()
    if not pendentes:
        logger.info("Fila vazia — nenhum processo pendente.")
        return False

    processo = pendentes[0]
    id_processo: int = processo.get("id_processo") or processo.get("id", 0)
    numero_processo: str = formatar_numero_processo(processo.get("numero_processo", ""))
    tribunal: str    = (processo.get("tribunal") or "").upper()
    tipo_sistema: str = (processo.get("tipo_sistema") or "DESCONHECIDO").upper()
    data_ato_str   = processo.get("data_ato") or None   # "YYYY-MM-DD" ou None
    data_ato: Date | None = Date.fromisoformat(data_ato_str) if data_ato_str else None
    qtd_fila = len(pendentes)

    _inicio = time.time()

    logger.info(SEP)
    logger.info(f"▶  PROCESSO : {numero_processo}")
    logger.info(f"   Tribunal : {tribunal or '???'}  |  ID: {id_processo}  |  Na fila: {qtd_fila}")
    logger.info(f"   Tipo     : {tipo_sistema}")
    logger.info(SEP)

    # ── ETAPA 1: Registrar início da consulta ─────────────────────────────────
    logger.info("[1/8] Registrando início da consulta na API...")
    ok1 = api.registrar_consulta(id_processo)
    if ok1:
        logger.info("[1/8] ✓ Status atualizado → CONSULTANDO")
    else:
        logger.warning("[1/8] ⚠ Falha ao atualizar status (continuando)")
    api.registrar_log(id_processo, f"Consulta iniciada no tribunal {tribunal}")

    # ── ETAPA 1b: Verificar compatibilidade do tipo_sistema ───────────────────
    SISTEMAS_SUPORTADOS: dict[str, list[str]] = {"MG": ["PJE"]}
    tipos_do_tribunal = SISTEMAS_SUPORTADOS.get(tribunal, [])
    if tipo_sistema not in tipos_do_tribunal:
        msg = (
            f"Sistema '{tipo_sistema}' não suportado para {tribunal}. "
            f"Suportados: {tipos_do_tribunal or ['nenhum']}"
        )
        logger.warning(f"[1/8] ✗ {msg}")
        api.registrar_nao_compativel(id_processo, msg)
        api.registrar_log(id_processo, msg, "WARNING")
        logger.info(f"■ Concluído em {time.time() - _inicio:.1f}s")
        imprimir_relatorio(numero_processo, tribunal, 0, 0)
        return True
    logger.info(f"[1/8] ✓ Tipo {tipo_sistema} compatível para {tribunal}")

    # ── ETAPA 2: Verificar suporte ao tribunal ────────────────────────────────
    logger.info(f"[2/8] Verificando suporte ao tribunal '{tribunal}'...")
    if tribunal not in config.TRIBUNAIS_SUPORTADOS:
        msg = f"Tribunal não suportado: '{tribunal}'. Suportados: {list(config.TRIBUNAIS_SUPORTADOS.keys())}"
        logger.error(f"[2/8] ✗ {msg}")
        api.registrar_erro(id_processo, msg)
        logger.info(f"■ Concluído em {time.time() - _inicio:.1f}s")
        imprimir_relatorio(numero_processo, tribunal, 0, 0)
        return True
    logger.info(f"[2/8] ✓ Tribunal {tribunal} suportado")

    driver = None
    atas_encontradas = 0
    atas_baixadas = 0

    try:
        # ── ETAPA 3: Iniciar WebDriver ────────────────────────────────────────
        modo_str = "headless" if headless else "visual"
        logger.info(f"[3/8] Iniciando WebDriver Chrome (modo {modo_str})...")
        _t3 = time.time()
        driver = criar_driver(headless=headless, download_dir=download_dir)
        logger.info(f"[3/8] ✓ WebDriver pronto ({time.time() - _t3:.1f}s)")

        # ── ETAPA 4: Carregar scraper ─────────────────────────────────────────
        logger.info(f"[4/8] Carregando scraper para {tribunal}...")
        scraper = carregar_scraper(tribunal, driver, download_dir)
        if not scraper:
            msg = f"Falha ao instanciar scraper para {tribunal}"
            logger.error(f"[4/8] ✗ {msg}")
            api.registrar_erro(id_processo, msg)
            logger.info(f"■ Concluído em {time.time() - _inicio:.1f}s")
            imprimir_relatorio(numero_processo, tribunal, 0, 0)
            return True
        logger.info(f"[4/8] ✓ Scraper {type(scraper).__name__} carregado")

        # ── ETAPA 5: Executar scraping ────────────────────────────────────────
        logger.info(f"[5/8] Iniciando scraping no portal {tribunal}...")
        _t5 = time.time()
        documentos: List[Documento] = scraper.executar(numero_processo)
        _dur5 = time.time() - _t5

        if not documentos:
            msg = "Processo não encontrado no portal ou bloqueio detectado"
            logger.warning(f"[5/8] ⚠ {msg} ({_dur5:.1f}s)")
            api.registrar_log(id_processo, msg, "WARNING")
            api.registrar_erro(id_processo, msg)
            logger.info(f"■ Concluído em {time.time() - _inicio:.1f}s")
            imprimir_relatorio(numero_processo, tribunal, 0, 0)
            return True

        atas: List[Documento] = [d for d in documentos if d.eh_ata]
        ignorados = [d for d in documentos if not d.eh_ata]

        # Filtra atas pela data_ato: descarta documentos anteriores ao ato
        if data_ato:
            antes = len(atas)
            atas = [
                a for a in atas
                if a.data_documento is None or a.data_documento >= data_ato
            ]
            descartadas = antes - len(atas)
            if descartadas:
                logger.info(
                    f"[5/8] Filtro data_ato ({data_ato}): "
                    f"{descartadas} ata(s) descartada(s) por data anterior ao ato"
                )

        # Filtra atas pelas extensões aceitas (configuração do painel)
        cfg_sistema          = api.buscar_configuracoes()
        extensoes_aceitas    = {e.lower().strip() for e in cfg_sistema.get("extensoes_aceitas", ["pdf", "html"]) if e.strip()}
        if extensoes_aceitas:
            antes_ext = len(atas)
            atas = [a for a in atas if a.formato.lower() in extensoes_aceitas]
            descartadas_ext = antes_ext - len(atas)
            if descartadas_ext:
                logger.info(
                    f"[5/8] Filtro extensão {extensoes_aceitas}: "
                    f"{descartadas_ext} ata(s) ignorada(s) — formato não permitido"
                )
            logger.debug(f"[5/8] Extensões aceitas: {extensoes_aceitas}")

        atas_encontradas = len(atas)
        logger.info(
            f"[5/8] ✓ Scraping concluído — "
            f"{len(documentos)} doc(s) | {atas_encontradas} ata(s) | "
            f"{len(ignorados)} ignorado(s) ({_dur5:.1f}s)"
        )

        # ── Sem atas: finaliza sem download ───────────────────────────────────
        if not atas:
            logger.info("[6/8] Nenhuma ata encontrada — finalizando processo na API...")
            api.registrar_log(id_processo, "Nenhuma ata encontrada para o processo")
            ok_sem = api.registrar_sem_ata(id_processo)
            if ok_sem:
                logger.info("[6/8] ✓ Processo marcado como FINALIZADO (sem ata)")
            else:
                logger.warning("[6/8] ⚠ Falha ao registrar 'sem ata' na API")
            logger.info(f"■ Concluído em {time.time() - _inicio:.1f}s")
            imprimir_relatorio(numero_processo, tribunal, 0, 0)
            return True

        # ── ETAPA 6: Download das atas ────────────────────────────────────────
        logger.info(f"[6/8] Iniciando download de {atas_encontradas} ata(s)...")
        nomes_baixados: List[str] = []

        for i, ata in enumerate(atas, start=1):
            ata.indice = i
            logger.info(
                f"[6/8] Ata {i}/{atas_encontradas}: '{ata.texto[:55]}' ({ata.formato.upper()})"
            )
            if ata.formato == "pdf":
                ok, nome, caminho, tamanho = downloader.baixar_pdf(
                    url=ata.url, driver=driver, tribunal=tribunal,
                    numero_processo=numero_processo, indice=i,
                )
            else:
                ok, nome, caminho, tamanho = downloader.baixar_html(
                    url=ata.url, driver=driver, tribunal=tribunal,
                    numero_processo=numero_processo, indice=i,
                )

            ata.download_ok = ok
            ata.nome_arquivo = nome
            ata.caminho_arquivo = caminho
            ata.tamanho_bytes = tamanho

            if ok:
                kb = (tamanho or 0) / 1024
                nomes_baixados.append(nome)
                atas_baixadas += 1
                logger.info(f"[6/8] ✓ Ata {i}/{atas_encontradas}: {nome} ({kb:.0f} KB)")
                api.registrar_log(id_processo, f"Ata baixada: {nome}", "INFO")
            else:
                logger.warning(f"[6/8] ⚠ Falha no download da ata {i}/{atas_encontradas}")
                api.registrar_log(id_processo, f"Falha no download da ata {i}", "ERROR")

            # Registra o arquivo na tabela processos_arquivos (sucesso ou falha)
            id_arq = api.registrar_arquivo(
                id_processo=id_processo,
                nome_arquivo=nome or f"ata_{i}_{tribunal}",
                caminho_arquivo=caminho,
                formato=ata.formato,
                tamanho_bytes=tamanho or 0,
                texto_doc=ata.texto[:500],
                indice=i,
                download_ok=ok,
            )
            if id_arq == -1:
                # Arquivo ignorado pelo servidor (extensão não permitida) — não é erro
                logger.info(f"[6/8] — Ata {i}/{atas_encontradas}: ignorada pelo servidor (extensão não aceita)")
            elif id_arq:
                logger.debug(f"[6/8] Arquivo {i} registrado na tabela processos_arquivos (id={id_arq})")
                # Envia o arquivo para o VPS para que o download funcione no painel
                if ok and caminho:
                    upload_ok = api.upload_arquivo(id_arq, caminho)
                    if upload_ok:
                        logger.info(f"[6/8] ✓ Arquivo {i} enviado para o servidor")
                    else:
                        logger.warning(f"[6/8] ⚠ Falha ao enviar arquivo {i} para o servidor (download no painel pode não funcionar)")
            else:
                logger.warning(f"[6/8] ⚠ Falha ao registrar arquivo {i} na tabela processos_arquivos")

        # ── ETAPA 7: Registrar resultado final ────────────────────────────────
        logger.info("[7/8] Registrando resultado na API...")
        if nomes_baixados:
            ok7 = api.registrar_ata(id_processo, atas_baixadas, nomes_baixados)
            if ok7:
                logger.info(f"[7/8] ✓ {atas_baixadas} ata(s) registrada(s) com sucesso")
            else:
                logger.warning("[7/8] ⚠ Falha ao registrar atas na API")
            api.registrar_log(
                id_processo,
                f"Consulta finalizada: {atas_baixadas} ata(s) registrada(s)",
            )
        else:
            logger.warning(f"[7/8] ⚠ Nenhuma ata baixada ({atas_encontradas} encontrada(s))")
            api.registrar_log(
                id_processo,
                f"Atas encontradas ({atas_encontradas}) mas nenhuma baixada com sucesso",
                "ERROR",
            )
            api.registrar_erro(id_processo, "Falha no download de todas as atas")

    except KeyboardInterrupt:
        logger.info("Interrompido pelo usuário (Ctrl+C)")
        raise

    except Exception as e:
        msg = f"Erro inesperado: {type(e).__name__}: {e}"
        logger.error(f"✗ {msg}")
        api.registrar_erro(id_processo, msg)

    finally:
        logger.info("[8/8] Encerrando WebDriver...")
        if driver:
            try:
                driver.quit()
                logger.info("[8/8] ✓ WebDriver encerrado")
            except Exception:
                logger.warning("[8/8] ⚠ Falha ao encerrar WebDriver")
        else:
            logger.info("[8/8] — WebDriver não foi iniciado")

    logger.info(f"✓ Processo concluído em {time.time() - _inicio:.1f}s")
    imprimir_relatorio(numero_processo, tribunal, atas_encontradas, atas_baixadas)
    return True


# ── Entry point ───────────────────────────────────────────────────────────────

def _parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Automação de consulta de processos jurídicos brasileiros",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Exemplos:
  python main.py                          Processa 1 processo (modo padrão)
  python main.py --loop                   Processa fila completa
  python main.py --headless --loop        Modo servidor sem janela
  python main.py --log-level DEBUG        Diagnóstico detalhado
  python main.py --download-dir D:/atas  Define pasta personalizada
        """,
    )
    parser.add_argument(
        "--loop",
        action="store_true",
        help="Processar continuamente até a fila esvaziar",
    )
    parser.add_argument(
        "--headless",
        action="store_true",
        help="Executar Chrome sem interface gráfica",
    )
    parser.add_argument(
        "--download-dir",
        type=str,
        default="",
        metavar="DIR",
        help="Diretório de destino dos downloads (padrão: python/downloads/)",
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

    # Configura logger antes de qualquer outra operação
    logger = configurar_logger(nivel=args.log_level)
    logger.info("=" * 60)
    logger.info("Sistema de automação de processos jurídicos iniciado")
    logger.info(f"Modo: {'loop' if args.loop else 'único'} | "
                f"Headless: {args.headless} | "
                f"Log: {args.log_level}")
    logger.info("=" * 60)

    # Diretório de download (CLI sobrescreve o padrão do config)
    download_dir = args.download_dir or config.DEFAULT_DOWNLOAD_DIR

    # Instancia clientes compartilhados (reutilizados em todo o loop)
    api = APIClient(
        base_url=config.API_BASE_URL,
        token=config.API_TOKEN,
        timeout=config.API_TIMEOUT,
    )
    downloader = Downloader(diretorio_base=download_dir)

    try:
        if args.loop:
            logger.info("Modo loop ativado — processando até fila vazia")
            ciclo = 0
            while True:
                ciclo += 1
                logger.debug(f"Iniciando ciclo #{ciclo}")
                houve_processo = processar_um(api, downloader, args.headless, download_dir)
                if not houve_processo:
                    logger.info("Fila vazia. Encerrando modo loop.")
                    break
                logger.info(
                    f"Ciclo #{ciclo} concluído. "
                    f"Aguardando {config.LOOP_INTERVAL_SECONDS}s para próximo..."
                )
                time.sleep(config.LOOP_INTERVAL_SECONDS)
        else:
            processar_um(api, downloader, args.headless, download_dir)

    except KeyboardInterrupt:
        logger.info("Execução interrompida pelo usuário (Ctrl+C)")
        sys.exit(0)

    logger.info("Sistema encerrado")


if __name__ == "__main__":
    main()

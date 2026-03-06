"""
Factory para criação do WebDriver Selenium (Chrome).

Centraliza configurações do ChromeDriver para garantir comportamento
consistente entre execuções com e sem interface gráfica.
"""

import logging
import tempfile

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.remote.webdriver import WebDriver
from webdriver_manager.chrome import ChromeDriverManager

logger = logging.getLogger("automacao")


def criar_driver(headless: bool = False, download_dir: str = "") -> WebDriver:
    """
    Cria e retorna uma instância configurada do Chrome WebDriver.

    O webdriver-manager baixa e gerencia automaticamente o ChromeDriver
    compatível com a versão do Chrome instalada.

    Args:
        headless:     Se True, executa sem abrir janela do navegador.
        download_dir: Diretório para downloads automáticos do Chrome.
                      Se vazio, usa diretório temporário do sistema.

    Returns:
        Instância do WebDriver pronta para uso.

    Raises:
        Exception: Se o ChromeDriver não puder ser iniciado.
    """
    options = _montar_opcoes(headless, download_dir)

    try:
        service = Service(ChromeDriverManager().install())
        driver = webdriver.Chrome(service=service, options=options)

        # Remove assinatura de automação do navigator.webdriver
        driver.execute_cdp_cmd(
            "Page.addScriptToEvaluateOnNewDocument",
            {
                "source": (
                    "Object.defineProperty(navigator, 'webdriver', "
                    "{get: () => undefined});"
                )
            },
        )

        modo = "headless" if headless else "com interface"
        logger.info(f"WebDriver Chrome criado ({modo})")
        return driver

    except Exception as e:
        logger.error(f"Falha ao criar WebDriver: {e}")
        raise


def _montar_opcoes(headless: bool, download_dir: str) -> Options:
    """Constrói as Options do Chrome com todas as configurações necessárias."""
    options = Options()

    if headless:
        options.add_argument("--headless=new")   # Modo headless moderno (Chrome 112+)
        options.add_argument("--disable-gpu")

    # Configurações de estabilidade e compatibilidade
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--window-size=1920,1080")
    options.add_argument("--lang=pt-BR,pt")
    options.add_argument("--disable-extensions")
    options.add_argument("--disable-popup-blocking")

    # Reduz detecção de automação por parte dos portais
    options.add_argument("--disable-blink-features=AutomationControlled")
    options.add_experimental_option("excludeSwitches", ["enable-automation"])
    options.add_experimental_option("useAutomationExtension", False)

    # Preferências de download: PDF é salvo em vez de aberto no viewer
    pasta_download = download_dir or tempfile.mkdtemp()
    prefs = {
        "download.default_directory": pasta_download,
        "download.prompt_for_download": False,
        "download.directory_upgrade": True,
        "plugins.always_open_pdf_externally": True,
        "profile.default_content_settings.popups": 0,
        "profile.content_settings.exceptions.automatic_downloads.*.setting": 1,
    }
    options.add_experimental_option("prefs", prefs)

    return options

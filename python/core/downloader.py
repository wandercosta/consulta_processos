"""
Responsável pelo download de documentos (PDF e HTML) encontrados nos tribunais.

Utiliza cookies do Selenium para manter autenticação na sessão de download,
garantindo que documentos que exigem sessão ativa sejam baixados corretamente.
"""

import logging
import os
from datetime import datetime
from typing import Optional, Tuple

import requests
from selenium.webdriver.remote.webdriver import WebDriver

logger = logging.getLogger("automacao")

# Content-Types aceitos por formato de documento
_CONTENT_TYPES_PDF = ["application/pdf", "application/octet-stream"]
_CONTENT_TYPES_HTML = ["text/html", "application/xhtml+xml"]


class Downloader:
    """
    Gerencia o download de documentos do tribunal.

    Cada método retorna uma tupla:
        (sucesso: bool, nome_arquivo: str|None, caminho: str|None, tamanho_bytes: int)
    """

    def __init__(self, diretorio_base: str) -> None:
        """
        Args:
            diretorio_base: Pasta raiz onde os downloads serão organizados por tribunal.
        """
        self.diretorio_base = diretorio_base

    # ── Métodos públicos ──────────────────────────────────────────────────────

    def baixar_pdf(
        self,
        url: str,
        driver: WebDriver,
        tribunal: str,
        numero_processo: str,
        indice: int,
    ) -> Tuple[bool, Optional[str], Optional[str], int]:
        """
        Baixa um PDF usando a sessão autenticada do Selenium.

        Args:
            url:             URL direta do PDF.
            driver:          Instância ativa do WebDriver (fornece cookies).
            tribunal:        Identificador do tribunal (usado na pasta e no nome).
            numero_processo: Número do processo (usado no nome do arquivo).
            indice:          Índice ordinal da ata (1, 2, 3...).

        Returns:
            (sucesso, nome_arquivo, caminho_absoluto, tamanho_bytes)
        """
        pasta = self._garantir_diretorio(tribunal)
        nome = self._gerar_nome("pdf", tribunal, numero_processo, indice)
        caminho = os.path.join(pasta, nome)

        try:
            session = self._sessao_com_cookies(driver)
            resp = session.get(url, timeout=60, stream=True)
            resp.raise_for_status()

            content_type = resp.headers.get("Content-Type", "")
            if not any(ct in content_type for ct in _CONTENT_TYPES_PDF):
                logger.warning(f"Content-Type inesperado para PDF: {content_type!r} ({url})")
                # Tenta salvar mesmo assim se houver conteúdo
                if not resp.content:
                    logger.error("Resposta vazia, abortando download do PDF")
                    return False, None, None, 0

            tamanho = 0
            with open(caminho, "wb") as f:
                for chunk in resp.iter_content(chunk_size=8192):
                    f.write(chunk)
                    tamanho += len(chunk)

            logger.info(f"Download OK: {nome} ({tamanho // 1024} KB)")
            return True, nome, caminho, tamanho

        except requests.RequestException as e:
            logger.error(f"Erro de rede ao baixar PDF ({url}): {e}")
        except OSError as e:
            logger.error(f"Erro ao salvar PDF em {caminho}: {e}")
        except Exception as e:
            logger.error(f"Erro inesperado ao baixar PDF ({url}): {e}")

        return False, None, None, 0

    def baixar_html(
        self,
        url: str,
        driver: WebDriver,
        tribunal: str,
        numero_processo: str,
        indice: int,
    ) -> Tuple[bool, Optional[str], Optional[str], int]:
        """
        Baixa uma página HTML usando a sessão autenticada do Selenium.

        Args:
            url:             URL da página HTML do documento.
            driver:          Instância ativa do WebDriver (fornece cookies).
            tribunal:        Identificador do tribunal.
            numero_processo: Número do processo.
            indice:          Índice ordinal da ata.

        Returns:
            (sucesso, nome_arquivo, caminho_absoluto, tamanho_bytes)
        """
        pasta = self._garantir_diretorio(tribunal)
        nome = self._gerar_nome("html", tribunal, numero_processo, indice)
        caminho = os.path.join(pasta, nome)

        try:
            session = self._sessao_com_cookies(driver)
            resp = session.get(url, timeout=60)
            resp.raise_for_status()

            conteudo = resp.text
            tamanho = len(conteudo.encode("utf-8"))

            with open(caminho, "w", encoding="utf-8") as f:
                f.write(conteudo)

            logger.info(f"Download OK: {nome} ({tamanho // 1024} KB)")
            return True, nome, caminho, tamanho

        except requests.RequestException as e:
            logger.error(f"Erro de rede ao baixar HTML ({url}): {e}")
        except OSError as e:
            logger.error(f"Erro ao salvar HTML em {caminho}: {e}")
        except Exception as e:
            logger.error(f"Erro inesperado ao baixar HTML ({url}): {e}")

        return False, None, None, 0

    # ── Métodos auxiliares privados ───────────────────────────────────────────

    def _garantir_diretorio(self, tribunal: str) -> str:
        """Cria e retorna o diretório de download do tribunal."""
        pasta = os.path.join(self.diretorio_base, tribunal)
        os.makedirs(pasta, exist_ok=True)
        return pasta

    def _gerar_nome(
        self,
        formato: str,
        tribunal: str,
        numero_processo: str,
        indice: int,
    ) -> str:
        """
        Gera nome padronizado do arquivo.

        Formato: {TRIBUNAL}_{numero_processo}_ATA_{indice}_{DDMMAAAA}.{extensão}
        Ex: TJMG_5000213-62.2026.8.13.0521_ATA_1_06032026.pdf
        """
        data = datetime.now().strftime("%d%m%Y")
        # Sanitiza o número do processo removendo caracteres inválidos em nomes de arquivo
        num = numero_processo.replace("/", "-").replace("\\", "-").replace(" ", "_")
        return f"{tribunal}_{num}_ATA_{indice}_{data}.{formato}"

    def _sessao_com_cookies(self, driver: WebDriver) -> requests.Session:
        """
        Cria uma requests.Session com os cookies e User-Agent do Selenium.

        Isso permite que o download via requests mantenha a mesma sessão
        autenticada do navegador, essencial para documentos protegidos.
        """
        session = requests.Session()

        for cookie in driver.get_cookies():
            session.cookies.set(
                cookie["name"],
                cookie["value"],
                domain=cookie.get("domain"),
                path=cookie.get("path", "/"),
            )

        try:
            user_agent = driver.execute_script("return navigator.userAgent;")
        except Exception:
            user_agent = "Mozilla/5.0"

        session.headers.update({
            "User-Agent": user_agent,
            "Referer": driver.current_url,
            "Accept": "application/pdf,text/html,application/xhtml+xml,*/*",
        })

        return session

"""
Scraper para o PJe público do TJRJ (Tribunal de Justiça do Rio de Janeiro).

Portal: https://tjrj.pje.jus.br/pje/ConsultaPublica/listView.seam

O TJRJ usa o mesmo sistema PJe que o TJMG, com diferenças pontuais:
  - URL base e de consulta distintas
  - Placeholder do campo de processo: '8.19' (TJRJ) vs '8.13' (TJMG)
  - Identificador do tribunal nos Documentos: 'TJRJ'

Por isso, essa classe herda TJMGPJeScraper e sobrescreve apenas o que muda.
"""

import logging
import re
from typing import List, Optional
from urllib.parse import urljoin

from selenium.common.exceptions import (
    NoSuchElementException,
    TimeoutException,
    WebDriverException,
)
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait

from models.documento import Documento
from tribunais.tjmg_pje import TJMGPJeScraper

logger = logging.getLogger("automacao")

_URL_BASE_RJ    = "https://tjrj.pje.jus.br"
_URL_CONSULTA_RJ = f"{_URL_BASE_RJ}/pje/ConsultaPublica/listView.seam"


class TJRJPJeScraper(TJMGPJeScraper):
    """
    Scraper para o PJe público do TJRJ.

    Reutiliza toda a lógica do TJMG (pesquisa, extração de detalhe,
    mapeamento de documentos) e sobrescreve apenas o que difere:
    URL base, placeholder do campo de processo e nome do tribunal.
    """

    # ── Propriedades ──────────────────────────────────────────────────────────

    @property
    def nome_tribunal(self) -> str:
        return "TJRJ"

    @property
    def url_consulta(self) -> str:
        return _URL_CONSULTA_RJ

    # ── Fluxo ─────────────────────────────────────────────────────────────────

    def abrir_consulta(self) -> bool:
        """Navega para a consulta pública do TJRJ e aguarda o campo de processo."""
        try:
            logger.info(f"[TJRJ] Abrindo consulta: {_URL_CONSULTA_RJ}")
            self.driver.get(_URL_CONSULTA_RJ)

            # Placeholder do TJRJ é '8.19', diferente do TJMG '8.13'
            self._wait.until(
                EC.presence_of_element_located(
                    (By.CSS_SELECTOR, "input[placeholder*='8.19'], input[id*='processo'], input[name*='processo']")
                )
            )
            logger.info("[TJRJ] Página de consulta carregada")
            return True

        except TimeoutException:
            logger.error("[TJRJ] Timeout: campo de processo não apareceu em 10s")
            return False
        except WebDriverException as e:
            logger.error(f"[TJRJ] Erro ao abrir consulta: {e}")
            return False

    # ── Auxiliares privados sobrescritos ──────────────────────────────────────

    def _localizar_campo_processo(self):
        """
        Localiza o campo de número de processo no portal TJRJ.
        Prioriza o placeholder '8.19' característico do TJRJ.
        """
        seletores = [
            (By.CSS_SELECTOR, "input[placeholder*='8.19']"),
            (By.CSS_SELECTOR, "input[placeholder*='processo']"),
            (By.CSS_SELECTOR, "input[id*='processo']"),
            (By.CSS_SELECTOR, "input[name*='processo']"),
            (By.XPATH, "//label[contains(translate(text(),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'processo')]/following-sibling::input"),
            (By.XPATH, "//input[@type='text'][1]"),
        ]
        for by, seletor in seletores:
            try:
                elem = WebDriverWait(self.driver, 5).until(
                    EC.presence_of_element_located((by, seletor))
                )
                if elem.is_displayed() and elem.is_enabled():
                    return elem
            except (TimeoutException, NoSuchElementException):
                continue
        return None

    def _extrair_url_detalhe(self) -> Optional[str]:
        """
        Extrai a URL de detalhe usando a base do TJRJ.

        Mesmo padrão de onclick do TJMG, mas URLs relativas são
        resolvidas contra tjrj.pje.jus.br em vez de tjmg.
        """
        try:
            candidatos = self.driver.find_elements(
                By.XPATH,
                "//*[contains(@onclick,'DetalheProcesso') or "
                "contains(@onclick,'detalhe') or "
                "contains(@onclick,'openPopUp')]",
            )

            for elem in candidatos:
                onclick = elem.get_attribute("onclick") or ""
                href = elem.get_attribute("href") or ""

                match = re.search(r"openPopUp\('[^']*',\s*'([^']+)'", onclick)
                if match:
                    return urljoin(_URL_BASE_RJ, match.group(1))

                match = re.search(r"window\.open\(['\"]([^'\"]+)['\"]", onclick)
                if match:
                    return urljoin(_URL_BASE_RJ, match.group(1))

                if href and "detalhe" in href.lower() and href.startswith("http"):
                    return href

        except Exception as e:
            logger.debug(f"[TJRJ] Exceção ao extrair URL de detalhe: {e}")

        return None

    def _extrair_url_onclick(self, onclick: str) -> str:
        """
        Extrai URL de onclick resolvendo caminhos relativos contra a base do TJRJ.
        """
        padroes = [
            r"openPopUp\('[^']*',\s*'([^']+)'",
            r"documentoSemLoginHTML\('[^']*',\s*'([^']+)'",
            r"window\.open\(['\"]([^'\"]+)['\"]",
            r"location\.href\s*=\s*['\"]([^'\"]+)['\"]",
        ]
        for padrao in padroes:
            match = re.search(padrao, onclick)
            if match:
                url = match.group(1)
                if not url.startswith("http"):
                    url = urljoin(_URL_BASE_RJ, url)
                return url
        return ""

    def mapear_documentos(self, numero_processo: str) -> List[Documento]:
        """
        Mapeia documentos da página de detalhe do TJRJ.

        Delega ao TJMG e corrige o campo tribunal de 'TJMG' para 'TJRJ'.
        """
        documentos = super().mapear_documentos(numero_processo)
        for doc in documentos:
            doc.tribunal = "TJRJ"
        return documentos

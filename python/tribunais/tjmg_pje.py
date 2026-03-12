"""
Scraper para o PJe público do TJMG (Tribunal de Justiça de Minas Gerais).

Portal: https://pje-consulta-publica.tjmg.jus.br/pje/ConsultaPublica/listView.seam

Fluxo implementado:
  1. Abrir página de consulta pública
  2. Preencher campo "Processo" e pesquisar
  3. Extrair URL de detalhe do resultado (via onclick → openPopUp)
  4. Navegar para o detalhe
  5. Mapear todos os documentos juntados
  6. Classificação (feita pelo BaseScraper)
"""

import logging
import re
import time
from datetime import datetime
from typing import List, Optional
from urllib.parse import urljoin

from selenium.common.exceptions import (
    NoSuchElementException,
    StaleElementReferenceException,
    TimeoutException,
    WebDriverException,
)
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.remote.webdriver import WebDriver
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait

from models.documento import Documento
from tribunais.base_scraper import BaseScraper

logger = logging.getLogger("automacao")

_URL_BASE = "https://pje-consulta-publica.tjmg.jus.br"
_URL_CONSULTA = f"{_URL_BASE}/pje/ConsultaPublica/listView.seam"

# Padrões de onclick usados pelo PJe para abrir popups de documento
_PADROES_ONCLICK_URL = [
    r"openPopUp\('[^']*',\s*'([^']+)'",
    r"documentoSemLoginHTML\('[^']*',\s*'([^']+)'",
    r"window\.open\(['\"]([^'\"]+)['\"]",
    r"location\.href\s*=\s*['\"]([^'\"]+)['\"]",
]


class TJMGPJeScraper(BaseScraper):
    """
    Implementação do scraper para o portal PJe de consulta pública do TJMG.

    Herda BaseScraper e implementa todos os métodos abstratos
    seguindo o comportamento específico do portal pje-consulta-publica.tjmg.jus.br.
    """

    def __init__(self, driver: WebDriver, download_dir: str) -> None:
        super().__init__(driver, download_dir)
        self._wait = WebDriverWait(driver, 10)

    # ── Propriedades ──────────────────────────────────────────────────────────

    @property
    def nome_tribunal(self) -> str:
        return "TJMG"

    @property
    def url_consulta(self) -> str:
        return _URL_CONSULTA

    # ── Implementação do fluxo ────────────────────────────────────────────────

    def abrir_consulta(self) -> bool:
        """Navega para a consulta pública e aguarda o campo de processo."""
        try:
            logger.info(f"[TJMG] Abrindo consulta: {_URL_CONSULTA}")
            self.driver.get(_URL_CONSULTA)

            # Aguarda campo de número de processo estar visível
            self._wait.until(
                EC.presence_of_element_located(
                    (By.CSS_SELECTOR, "input[placeholder*='8.13'], input[id*='processo'], input[name*='processo']")
                )
            )
            logger.info("[TJMG] Página de consulta carregada")
            return True

        except TimeoutException:
            logger.error("[TJMG] Timeout: campo de processo não apareceu em 10s")
            return False
        except WebDriverException as e:
            logger.error(f"[TJMG] Erro ao abrir consulta: {e}")
            return False

    def pesquisar_processo(self, numero_processo: str) -> bool:
        """Preenche o número do processo e submete a pesquisa."""
        try:
            campo = self._localizar_campo_processo()
            if not campo:
                logger.error("[TJMG] Campo de número de processo não encontrado")
                return False

            campo.clear()
            time.sleep(0.3)
            campo.send_keys(numero_processo)
            time.sleep(0.5)

            botao = self._localizar_botao_pesquisar()
            if botao:
                botao.click()
            else:
                logger.debug("[TJMG] Botão pesquisar não encontrado, usando Enter")
                campo.send_keys(Keys.RETURN)

            logger.info(f"[TJMG] Pesquisando: {numero_processo}")

            # Aguarda resultados ou mensagem de "não encontrado"
            try:
                self._wait.until(
                    lambda d: self._tem_resultados(d) or self._sem_resultados(d)
                )
            except TimeoutException:
                logger.warning("[TJMG] Timeout aguardando resultados da pesquisa")
                # Verifica o estado atual mesmo após timeout
                return self._tem_resultados(self.driver)

            if self._sem_resultados(self.driver):
                logger.warning(f"[TJMG] Nenhum resultado para: {numero_processo}")
                return False

            encontrou = self._tem_resultados(self.driver)
            if encontrou:
                logger.info("[TJMG] Resultado encontrado no PJe")
            return encontrou

        except StaleElementReferenceException:
            logger.warning("[TJMG] Elemento ficou stale durante pesquisa, retentando")
            return False
        except Exception as e:
            logger.error(f"[TJMG] Erro durante pesquisa: {e}")
            return False

    def abrir_detalhe(self, numero_processo: str) -> bool:
        """
        Extrai a URL de detalhe do resultado e navega até ela.

        O PJe usa onclick com openPopUp para abrir o detalhe em popup.
        Aqui, extraímos a URL e navegamos diretamente.
        """
        try:
            url_detalhe = self._extrair_url_detalhe()

            if url_detalhe:
                logger.info(f"[TJMG] Navegando para detalhe: {url_detalhe}")
                self.driver.get(url_detalhe)
            else:
                # Fallback: tenta clicar diretamente no resultado
                logger.debug("[TJMG] URL de detalhe não extraída via onclick, tentando clique direto")
                if not self._clicar_primeiro_resultado():
                    logger.error("[TJMG] Não foi possível abrir o detalhe do processo")
                    return False

            # Aguarda indicativo de que a página de detalhe carregou
            try:
                self._wait.until(
                    EC.presence_of_element_located((
                        By.XPATH,
                        "//*[contains(text(),'Documentos') or "
                        "contains(text(),'juntados') or "
                        "contains(@id,'documento') or "
                        "contains(@class,'documento')]"
                    ))
                )
            except TimeoutException:
                # Página pode ter carregado sem a seção de documentos visível ainda
                logger.debug("[TJMG] Seção 'Documentos' não localizada no tempo esperado (continuando)")

            logger.info("[TJMG] Página de detalhe aberta com sucesso")
            return True

        except Exception as e:
            logger.error(f"[TJMG] Erro ao abrir detalhe: {e}")
            return False

    def mapear_documentos(self, numero_processo: str) -> List[Documento]:
        """
        Varre a página de detalhe em busca de links para documentos juntados.

        Dois métodos de identificação:
          - MÉTODO PDF:  href contém 'idBin'
          - MÉTODO HTML: onclick contém 'documentoSemLoginHTML' ou 'openPopUp'
        """
        documentos: List[Documento] = []

        try:
            # Pausa para garantir carregamento completo de elementos dinâmicos
            time.sleep(2)

            links = self.driver.find_elements(By.TAG_NAME, "a")
            indice = 0

            for link in links:
                try:
                    doc = self._extrair_documento_de_link(link, numero_processo, indice)
                    if doc:
                        documentos.append(doc)
                        indice += 1
                        logger.debug(f"[TJMG] Documento mapeado: {doc.texto[:50]}")
                except StaleElementReferenceException:
                    continue  # Elemento desapareceu durante iteração

            logger.info(f"[TJMG] {len(documentos)} documento(s) mapeado(s) na página de detalhe")

        except Exception as e:
            logger.error(f"[TJMG] Erro ao mapear documentos: {e}")

        return documentos

    def detectar_bloqueio(self) -> str:
        """
        Verifica captcha, acesso negado e redirecionamento para login.

        Returns:
            Descrição do bloqueio, ou "" se não houver.
        """
        try:
            # ── Captcha ───────────────────────────────────────────────────────
            iframes = self.driver.find_elements(By.TAG_NAME, "iframe")
            for iframe in iframes:
                src = (iframe.get_attribute("src") or "").lower()
                if "recaptcha" in src or "captcha" in src:
                    return "CAPTCHA (iframe reCAPTCHA detectado)"

            elementos_captcha = self.driver.find_elements(
                By.CSS_SELECTOR,
                ".g-recaptcha, #recaptcha, [class*='recaptcha'], [id*='captcha']",
            )
            if elementos_captcha:
                return "CAPTCHA (elemento na página)"

            # ── Acesso negado ─────────────────────────────────────────────────
            titulo = self.driver.title.lower()
            if any(kw in titulo for kw in ["forbidden", "negado", "denied", "403", "401"]):
                return "Acesso negado (título da página)"

            # Verifica texto na página (apenas primeiros 5000 chars para não ser lento)
            texto_pagina = self.driver.page_source[:5000].lower()
            if any(kw in texto_pagina for kw in ["acesso negado", "access denied", "forbidden"]):
                return "Acesso negado (conteúdo da página)"

            # ── Redirecionamento para login ────────────────────────────────────
            url_atual = self.driver.current_url.lower()
            if "login" in url_atual and "consulta" not in url_atual:
                return "Redirecionado para login"

        except WebDriverException:
            pass  # Se o driver falhou, retorna sem bloqueio detectado

        return ""

    # ── Métodos auxiliares privados ───────────────────────────────────────────

    def _localizar_campo_processo(self):
        """Tenta localizar o campo de número de processo por múltiplos seletores."""
        seletores = [
            (By.CSS_SELECTOR, "input[placeholder*='8.13']"),
            (By.CSS_SELECTOR, "input[placeholder*='processo']"),
            (By.CSS_SELECTOR, "input[id*='processo']"),
            (By.CSS_SELECTOR, "input[name*='processo']"),
            (By.XPATH, "//label[contains(translate(text(),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'processo')]/following-sibling::input"),
            (By.XPATH, "//input[@type='text'][1]"),  # Último recurso: primeiro input de texto
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

    def _localizar_botao_pesquisar(self):
        """Tenta localizar o botão de pesquisa por múltiplos seletores."""
        seletores = [
            (By.XPATH, "//button[contains(translate(normalize-space(.),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'pesquisar')]"),
            (By.XPATH, "//input[@type='submit'][contains(translate(@value,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'pesquisar')]"),
            (By.XPATH, "//button[@type='submit']"),
            (By.CSS_SELECTOR, "button[id*='pesquisar'], button[class*='pesquisar']"),
            (By.CSS_SELECTOR, "input[type='submit']"),
        ]
        for by, seletor in seletores:
            try:
                elem = self.driver.find_element(by, seletor)
                if elem.is_displayed() and elem.is_enabled():
                    return elem
            except NoSuchElementException:
                continue
        return None

    def _tem_resultados(self, driver) -> bool:
        """Verifica se há ao menos um resultado na página de listagem."""
        try:
            # Tabela de resultados com ID ou classe relacionada a 'resultado' ou 'lista'
            tabelas = driver.find_elements(
                By.XPATH,
                "//table[contains(@id,'resultado') or contains(@class,'resultado') "
                "or contains(@id,'lista') or contains(@class,'lista')]//tr[position()>1]",
            )
            if tabelas:
                return True

            # Elementos clicáveis com referência a detalhe
            elementos = driver.find_elements(
                By.XPATH,
                "//*[contains(@onclick,'Detalhe') or contains(@onclick,'detalhe') "
                "or contains(@onclick,'openPopUp')]",
            )
            return len(elementos) > 0
        except Exception:
            return False

    def _sem_resultados(self, driver) -> bool:
        """Verifica se a página indica ausência de resultados."""
        try:
            texto = driver.page_source[:8000].lower()
            termos_negativo = [
                "nenhum processo",
                "nenhum resultado",
                "não encontrado",
                "nao encontrado",
                "0 resultado",
                "sem resultado",
                "não há registros",
                "nao ha registros",
            ]
            return any(t in texto for t in termos_negativo)
        except Exception:
            return False

    def _extrair_url_detalhe(self) -> Optional[str]:
        """
        Extrai a URL de detalhe do processo via atributo onclick dos resultados.

        O PJe TJMG usa padrão: onclick="openPopUp('arg1', '/pje/...')"
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

                # Tenta padrão openPopUp com URL relativa
                match = re.search(r"openPopUp\('[^']*',\s*'([^']+)'", onclick)
                if match:
                    return urljoin(_URL_BASE, match.group(1))

                # Tenta window.open
                match = re.search(r"window\.open\(['\"]([^'\"]+)['\"]", onclick)
                if match:
                    return urljoin(_URL_BASE, match.group(1))

                # Link direto com "detalhe" na URL
                if href and "detalhe" in href.lower() and href.startswith("http"):
                    return href

        except Exception as e:
            logger.debug(f"[TJMG] Exceção ao extrair URL de detalhe: {e}")

        return None

    def _clicar_primeiro_resultado(self) -> bool:
        """Fallback: clica no primeiro elemento clicável de resultado."""
        try:
            primeiro = WebDriverWait(self.driver, 8).until(
                EC.element_to_be_clickable((
                    By.XPATH,
                    "(//*[contains(@onclick,'Detalhe') or contains(@onclick,'detalhe')])[1]",
                ))
            )
            primeiro.click()
            time.sleep(3)
            return True
        except (TimeoutException, NoSuchElementException):
            return False

    def _extrair_documento_de_link(
        self,
        link,
        numero_processo: str,
        indice: int,
    ) -> Optional[Documento]:
        """
        Analisa um elemento <a> e retorna Documento se for um documento relevante.

        Identifica o método de acesso (PDF via idBin, HTML via onclick)
        e monta a URL correta.
        """
        try:
            texto = (link.text or "").strip()
            href = link.get_attribute("href") or ""
            onclick = link.get_attribute("onclick") or ""

            # Descarta links sem texto significativo (menos de 4 chars)
            if not texto or len(texto) < 4:
                return None

            # Descarta links de navegação sem potencial de documento
            href_lower = href.lower()
            is_navegacao = (
                href_lower.startswith("javascript:void")
                or href_lower.startswith("mailto:")
                or (href_lower == "#" and not onclick)
            )
            if is_navegacao:
                return None

            url_doc = ""
            formato = "pdf"

            # ── MÉTODO PDF: href ou onclick com idBin ─────────────────────────
            if "idBin" in href or "idBin" in onclick:
                url_doc = href if "idBin" in href else self._extrair_url_onclick(onclick)
                formato = "pdf"

            # ── MÉTODO HTML: onclick com padrões de visualização HTML ──────────
            elif any(
                kw in onclick
                for kw in ["documentoSemLoginHTML", "SemLogin", "openPopUp", "visualizar"]
            ):
                url_doc = self._extrair_url_onclick(onclick)
                formato = "html"

            # ── Sem URL identificável → descarta ─────────────────────────────
            if not url_doc and not (href and href != "#"):
                return None

            url_final = url_doc or href
            if url_final and not url_final.startswith("http"):
                url_final = urljoin(_URL_BASE, url_final)

            # Tenta extrair data do documento a partir do texto da linha da tabela
            data_documento = None
            try:
                row = link.find_element(By.XPATH, "ancestor::tr[1]")
                match = re.search(r'\b(\d{2}/\d{2}/\d{4})\b', row.text)
                if match:
                    data_documento = datetime.strptime(match.group(1), '%d/%m/%Y').date()
            except Exception:
                pass

            return Documento(
                tribunal="TJMG",
                numero_processo=numero_processo,
                texto=texto,
                url=url_final,
                origem_url=self.driver.current_url,
                formato=formato,
                indice=indice,
                data_documento=data_documento,
            )

        except StaleElementReferenceException:
            return None
        except Exception:
            return None

    def _extrair_url_onclick(self, onclick: str) -> str:
        """
        Extrai URL de um atributo onclick usando padrões conhecidos do PJe.

        Retorna URL absoluta ou string vazia se não encontrado.
        """
        for padrao in _PADROES_ONCLICK_URL:
            match = re.search(padrao, onclick)
            if match:
                url = match.group(1)
                if not url.startswith("http"):
                    url = urljoin(_URL_BASE, url)
                return url
        return ""

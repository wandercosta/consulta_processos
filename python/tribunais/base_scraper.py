"""
Classe abstrata base para todos os scrapers de tribunais.

Define o contrato que cada implementação de tribunal deve seguir.
Novos tribunais devem herdar de BaseScraper e implementar os métodos abstratos.

Arquitetura:
    BaseScraper (contrato)
        └── TJMGPJeScraper   (implementação TJMG)
        └── TJSPEsajScraper  (futuro)
        └── TRF5PJeScraper   (futuro)
"""

import logging
from abc import ABC, abstractmethod
from typing import List

from selenium.webdriver.remote.webdriver import WebDriver

from models.documento import Documento

logger = logging.getLogger("automacao")


class BaseScraper(ABC):
    """
    Contrato base para scrapers de portais de tribunais.

    O método `executar()` orquestra o fluxo padrão chamando os métodos abstratos
    em sequência. Scrapers com fluxos muito diferentes podem sobrescrever `executar()`.
    """

    def __init__(self, driver: WebDriver, download_dir: str) -> None:
        """
        Args:
            driver:       Instância ativa do WebDriver.
            download_dir: Diretório base para downloads.
        """
        self.driver = driver
        self.download_dir = download_dir

    # ── Propriedades obrigatórias ─────────────────────────────────────────────

    @property
    @abstractmethod
    def nome_tribunal(self) -> str:
        """Identificador do tribunal em maiúsculas. Ex: 'TJMG'."""
        ...

    @property
    @abstractmethod
    def url_consulta(self) -> str:
        """URL da página de consulta pública do tribunal."""
        ...

    # ── Métodos abstratos do fluxo ────────────────────────────────────────────

    @abstractmethod
    def abrir_consulta(self) -> bool:
        """
        Navega para a página de consulta do tribunal.

        Returns:
            True se a página carregou com sucesso e está pronta para pesquisa.
        """
        ...

    @abstractmethod
    def pesquisar_processo(self, numero_processo: str) -> bool:
        """
        Preenche o formulário de pesquisa e submete.

        Args:
            numero_processo: Número completo do processo (ex: "5000213-62.2026.8.13.0521").

        Returns:
            True se ao menos um resultado foi encontrado.
        """
        ...

    @abstractmethod
    def abrir_detalhe(self, numero_processo: str) -> bool:
        """
        Navega para a página de detalhe do processo encontrado.

        Args:
            numero_processo: Número do processo (para validação ou log).

        Returns:
            True se a página de detalhe abriu com sucesso.
        """
        ...

    @abstractmethod
    def mapear_documentos(self, numero_processo: str) -> List[Documento]:
        """
        Varre a página de detalhe e retorna lista de documentos encontrados.

        Os documentos retornados ainda NÃO têm `eh_ata` definido —
        isso é feito pelo método `executar()` usando `classificar_documento()`.

        Args:
            numero_processo: Número do processo (usado nos objetos Documento).

        Returns:
            Lista de Documento com dados básicos preenchidos (texto, url, formato).
        """
        ...

    @abstractmethod
    def detectar_bloqueio(self) -> str:
        """
        Verifica se há captcha, acesso negado ou outros bloqueios na página atual.

        Returns:
            String descrevendo o bloqueio encontrado, ou "" se a página está OK.
        """
        ...

    # ── Fluxo padrão orquestrado ──────────────────────────────────────────────

    def executar(self, numero_processo: str) -> List[Documento]:
        """
        Executa o fluxo completo de scraping para um processo.

        Sequência:
          1. Abre consulta
          2. Detecta bloqueios
          3. Pesquisa processo
          4. Abre detalhe
          5. Mapeia documentos
          6. Classifica como ata ou não

        Tribunais com fluxos diferentes podem sobrescrever este método.

        Args:
            numero_processo: Número do processo a consultar.

        Returns:
            Lista de documentos com `eh_ata` classificado.
            Retorna lista vazia em qualquer falha de fluxo.
        """
        from core.utils import classificar_documento

        prefixo = f"[{self.nome_tribunal}]"

        # Etapa 1: Abrir página de consulta
        if not self.abrir_consulta():
            logger.error(f"{prefixo} Falha ao abrir página de consulta")
            return []

        # Etapa 2: Verificar bloqueios logo após carregar
        bloqueio = self.detectar_bloqueio()
        if bloqueio:
            logger.error(f"{prefixo} Bloqueio detectado: {bloqueio}")
            return []

        # Etapa 3: Pesquisar o processo
        if not self.pesquisar_processo(numero_processo):
            logger.warning(f"{prefixo} Processo não encontrado no portal: {numero_processo}")
            return []

        # Etapa 4: Abrir página de detalhe
        if not self.abrir_detalhe(numero_processo):
            logger.error(f"{prefixo} Falha ao abrir detalhe do processo")
            return []

        # Etapa 5: Mapear documentos
        documentos = self.mapear_documentos(numero_processo)

        # Etapa 6: Classificar cada documento
        for doc in documentos:
            doc.eh_ata = classificar_documento(doc.texto)
            if doc.eh_ata:
                logger.info(f"{prefixo} Ata encontrada: {doc.texto[:60]}")

        atas = [d for d in documentos if d.eh_ata]
        logger.info(
            f"{prefixo} {len(documentos)} documento(s) mapeado(s), "
            f"{len(atas)} ata(s) identificada(s)"
        )

        return documentos

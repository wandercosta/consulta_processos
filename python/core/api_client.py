"""
Cliente HTTP para comunicação com a API PHP local.

Responsabilidade única: enviar e receber dados da API.
Não contém lógica de negócio, scraping ou download.
"""

import logging
from typing import Any, Dict, List, Optional

import requests

logger = logging.getLogger("automacao")


class APIClient:
    """
    Encapsula todas as chamadas à API PHP local do projeto processos_api.

    Todos os métodos retornam None ou lista vazia em caso de falha,
    nunca lançam exceções para o chamador.
    """

    def __init__(self, base_url: str, token: str, timeout: int = 30) -> None:
        """
        Args:
            base_url: URL base da API (ex: "http://localhost/processos_api/api").
            token:    Token Bearer de autenticação.
            timeout:  Timeout em segundos para cada requisição.
        """
        self.base_url = base_url.rstrip("/")
        self.timeout = timeout
        self._session = requests.Session()
        self._session.headers.update({
            "Authorization": f"Bearer {token}",
            "Content-Type": "application/json",
            "Accept": "application/json",
        })

    # ── Métodos privados de transporte ────────────────────────────────────────

    def _url(self, endpoint: str) -> str:
        """
        Monta a URL completa usando o roteador PHP via query string.
        Ex: _url("processos_pendentes") → "http://localhost/processos_api/php/?endpoint=processos_pendentes"
        """
        return f"{self.base_url}/?endpoint={endpoint.lstrip('/')}"

    def _get(self, endpoint: str, params: Optional[Dict] = None) -> Optional[Any]:
        """Executa GET e retorna JSON parseado, ou None em falha."""
        url = self._url(endpoint)
        try:
            resp = self._session.get(url, params=params, timeout=self.timeout)
            resp.raise_for_status()
            logger.debug(f"GET /{endpoint} → {resp.status_code} OK")
            return resp.json()
        except requests.HTTPError as e:
            logger.error(f"HTTP {e.response.status_code} em GET /{endpoint} | URL: {url}")
        except requests.ConnectionError:
            logger.error(f"Sem conexão em GET /{endpoint} — WAMP está rodando? URL: {url}")
        except requests.Timeout:
            logger.error(f"Timeout em GET /{endpoint}")
        except Exception as e:
            logger.error(f"Erro inesperado em GET /{endpoint}: {e}")
        return None

    def _post(self, endpoint: str, dados: Dict) -> Optional[Any]:
        """Executa POST e retorna JSON parseado, ou None em falha."""
        url = self._url(endpoint)
        try:
            resp = self._session.post(url, json=dados, timeout=self.timeout)
            resp.raise_for_status()
            logger.debug(f"POST /{endpoint} → {resp.status_code} OK")
            return resp.json()
        except requests.HTTPError as e:
            logger.error(f"HTTP {e.response.status_code} em POST /{endpoint} | URL: {url}")
        except requests.ConnectionError:
            logger.error(f"Sem conexão em POST /{endpoint} — WAMP está rodando? URL: {url}")
        except requests.Timeout:
            logger.error(f"Timeout em POST /{endpoint}")
        except Exception as e:
            logger.error(f"Erro inesperado em POST /{endpoint}: {e}")
        return None

    # ── Endpoints públicos ────────────────────────────────────────────────────

    def buscar_processos_pendentes(self) -> List[Dict]:
        """
        GET /processos_pendentes

        Retorna lista de processos aguardando consulta.
        Cada item contém: id_processo, numero_processo, tribunal.
        """
        resultado = self._get("processos_pendentes")
        if isinstance(resultado, list):
            return resultado
        return []

    def registrar_consulta(self, id_processo: int) -> bool:
        """
        POST /registrar_consulta

        Marca o processo como CONSULTANDO na base de dados.
        Retorna True se a API confirmou.
        """
        resultado = self._post("registrar_consulta", {"id_processo": id_processo})
        return resultado is not None

    def registrar_log(
        self,
        id_processo: int,
        mensagem: str,
        status: str = "INFO",
    ) -> bool:
        """
        POST /logs

        Envia entrada de log para a API (persistida no banco).
        status: INFO | ERROR | WARNING
        """
        resultado = self._post("logs", {
            "id_processo": id_processo,
            "mensagem": mensagem,
            "status": status,
        })
        return resultado is not None

    def registrar_ata(
        self,
        id_processo: int,
        qtd_atas: int,
        arquivos: List[str],
    ) -> bool:
        """
        POST /registrar_ata

        Registra o sucesso da consulta com as atas baixadas.
        arquivos: lista com nomes dos arquivos (serão unidos por " | ").
        """
        arquivo_str = " | ".join(arquivos)
        resultado = self._post("registrar_ata", {
            "id_processo": id_processo,
            "qtd_atas": qtd_atas,
            "arquivo": arquivo_str,
        })
        return resultado is not None

    def registrar_sem_ata(self, id_processo: int) -> bool:
        """
        POST /registrar_sem_ata

        Marca o processo como FINALIZADO quando a consulta foi feita
        mas não há atas de audiência no portal.
        """
        resultado = self._post("registrar_sem_ata", {"id_processo": id_processo})
        return resultado is not None

    def registrar_erro(self, id_processo: int, mensagem: str) -> bool:
        """
        POST /registrar_erro

        Registra falha crítica no processo.
        Faz fallback para /logs com status ERROR se o endpoint não existir.
        """
        resultado = self._post("registrar_erro", {
            "id_processo": id_processo,
            "mensagem_erro": mensagem,
        })
        if resultado is None:
            # Fallback: usa /logs com status ERROR
            return self.registrar_log(id_processo, mensagem, "ERROR")
        return True

    def status_processo(self, id_processo: int) -> Optional[Dict]:
        """
        GET /status_processo?id={id}

        Consulta o status atual do processo na base de dados.
        """
        return self._get("status_processo", {"id": id_processo})

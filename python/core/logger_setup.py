"""
Configuração centralizada do sistema de logging.

Provê um logger compartilhado com saída simultânea para console e arquivo rotativo.
Importe e chame `configurar_logger()` uma única vez em main.py.
"""

import logging
import os
import sys
from logging.handlers import RotatingFileHandler
from typing import Optional


def configurar_logger(
    nome: str = "automacao",
    nivel: str = "INFO",
    log_file: Optional[str] = None,
) -> logging.Logger:
    """
    Configura e retorna o logger principal da aplicação.

    Após a primeira chamada, subsequentes chamadas com o mesmo `nome`
    retornam o logger já configurado (idempotente).

    Args:
        nome:      Nome do logger (padrão: "automacao").
        nivel:     Nível de log: DEBUG, INFO, WARNING, ERROR.
        log_file:  Caminho do arquivo de log. Se None, usa o padrão em config.py.

    Returns:
        Logger configurado com handlers de console e arquivo.
    """
    from config import LOG_DIR, LOG_FILE, LOG_FORMAT, LOG_DATE_FORMAT

    logger = logging.getLogger(nome)

    # Evita adicionar handlers duplicados se chamado mais de uma vez
    if logger.handlers:
        return logger

    nivel_numerico = getattr(logging, nivel.upper(), logging.INFO)
    logger.setLevel(nivel_numerico)

    formatter = logging.Formatter(LOG_FORMAT, datefmt=LOG_DATE_FORMAT)

    # ── Handler de console ────────────────────────────────────────────────────
    # IMPORTANTE: usa sys.stdout explicitamente (padrão seria stderr, que
    # ficaria invisível quando capturado via subprocess.Popen)
    console_handler = logging.StreamHandler(sys.stdout)
    console_handler.setFormatter(formatter)
    logger.addHandler(console_handler)

    # ── Handler de arquivo rotativo ───────────────────────────────────────────
    os.makedirs(LOG_DIR, exist_ok=True)
    arquivo = log_file or LOG_FILE

    file_handler = RotatingFileHandler(
        arquivo,
        maxBytes=5 * 1024 * 1024,  # 5 MB por arquivo
        backupCount=3,
        encoding="utf-8",
    )
    file_handler.setFormatter(formatter)
    logger.addHandler(file_handler)

    return logger

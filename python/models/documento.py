"""
Modelo padronizado para representar um documento encontrado nos portais dos tribunais.

Usado por todos os scrapers para garantir saída uniforme,
independentemente do tribunal ou do sistema consultado.
"""

from dataclasses import dataclass, field
from datetime import date as Date
from typing import Optional


@dataclass
class Documento:
    """
    Representa um documento encontrado na página de detalhe do processo.

    Campos obrigatórios devem ser informados na criação.
    Campos opcionais são preenchidos durante o download.
    """

    # ── Identificação ─────────────────────────────────────────────────────────
    tribunal: str           # Ex: "TJMG", "TJSP"
    numero_processo: str    # Ex: "5000213-62.2026.8.13.0521"
    texto: str              # Descrição/título do documento conforme exibido no portal

    # ── Metadados do documento ────────────────────────────────────────────────
    tipo_documento: str = ""     # Tipo inferido (ex: "ata", "despacho")
    url: str = ""                # URL de download ou acesso
    origem_url: str = ""         # URL da página onde o documento foi encontrado
    formato: str = "pdf"         # "pdf" ou "html"
    indice: int = 0              # Posição ordinal na lista de documentos da página

    # ── Data do documento ─────────────────────────────────────────────────────
    data_documento: Optional[Date] = None  # Data extraída da listagem do portal (dd/mm/yyyy)

    # ── Classificação ─────────────────────────────────────────────────────────
    eh_ata: bool = False         # True quando identificado como ata de audiência

    # ── Resultado do download (preenchido após tentativa) ─────────────────────
    nome_arquivo: Optional[str] = None      # Nome do arquivo salvo localmente
    caminho_arquivo: Optional[str] = None   # Caminho absoluto no sistema de arquivos
    tamanho_bytes: int = 0                  # Tamanho em bytes após download
    download_ok: bool = False               # True se o download foi bem-sucedido

    def __str__(self) -> str:
        tipo = "ATA" if self.eh_ata else "DOC"
        desc = self.texto[:60] + ("..." if len(self.texto) > 60 else "")
        return f"[{tipo}] {desc} ({self.formato.upper()})"

    def __repr__(self) -> str:
        return (
            f"Documento(tribunal={self.tribunal!r}, "
            f"numero_processo={self.numero_processo!r}, "
            f"texto={self.texto[:30]!r}, "
            f"eh_ata={self.eh_ata}, "
            f"formato={self.formato!r})"
        )

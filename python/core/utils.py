"""
Funções utilitárias compartilhadas entre módulos.

Aqui ficam funções puras e reutilizáveis que não pertencem
a nenhuma classe específica.
"""

import unicodedata
from typing import List

from config import PALAVRAS_CHAVE_ATA, PALAVRAS_IGNORAR


def normalizar_texto(texto: str) -> str:
    """
    Normaliza texto para comparação case-insensitive e sem acentos.

    Converte para minúsculas e remove diacríticos (acentos, cedilha, etc.)
    para que comparações funcionem independente de variações de escrita.

    Ex: "Ata de Audiência" → "ata de audiencia"
    """
    texto = texto.lower()
    # Decomposição canônica: separa caractere base de diacrítico
    texto = unicodedata.normalize("NFKD", texto)
    # Remove os diacríticos (combining characters)
    return "".join(c for c in texto if not unicodedata.combining(c))


def contem_palavra(texto: str, palavras: List[str]) -> bool:
    """
    Verifica se `texto` contém alguma das `palavras` (sem acentos, case-insensitive).

    Args:
        texto:    Texto a ser verificado.
        palavras: Lista de termos a procurar.

    Returns:
        True se qualquer termo for encontrado.
    """
    texto_norm = normalizar_texto(texto)
    return any(normalizar_texto(p) in texto_norm for p in palavras)


def classificar_documento(texto: str) -> bool:
    """
    Determina se um documento é uma ata de audiência.

    Regras (em ordem de prioridade):
      1. Se contiver palavra da lista IGNORAR → NÃO é ata.
      2. Se contiver palavra-chave de ATA → É ata.
      3. Caso contrário → NÃO é ata.

    A lógica é centralizada aqui para ser reutilizada por todos os scrapers,
    garantindo comportamento uniforme entre tribunais.

    Args:
        texto: Descrição/título do documento conforme exibido no portal.

    Returns:
        True se identificado como ata de audiência.
    """
    if contem_palavra(texto, PALAVRAS_IGNORAR):
        return False
    return contem_palavra(texto, PALAVRAS_CHAVE_ATA)


def formatar_numero_processo(numero: str) -> str:
    """Remove espaços extras e normaliza o número do processo."""
    return numero.strip()


def imprimir_relatorio(
    numero_processo: str,
    tribunal: str,
    atas_encontradas: int,
    atas_baixadas: int,
) -> None:
    """
    Imprime o relatório final do ciclo de processamento de um processo.

    Exibe sempre no console, independente do nível de log configurado.
    """
    if atas_baixadas > 0:
        status = "SUCESSO"
    elif atas_encontradas == 0:
        status = "SEM ATAS"
    else:
        status = "ERRO"

    linha = "━" * 50
    print(f"\n{linha}")
    print("RELATÓRIO FINAL")
    print(f"Processo   : {numero_processo}")
    print(f"Tribunal   : {tribunal}")
    print(f"Encontradas: {atas_encontradas}")
    print(f"Baixadas   : {atas_baixadas}")
    print(f"Status     : {status}")
    print(f"{linha}\n")

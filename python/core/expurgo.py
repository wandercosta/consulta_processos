"""
Rotina de expurgo automático de arquivos baixados.

Remove fisicamente arquivos com mais de DIAS_RETENCAO dias após o download,
registra cada exclusão na tabela arquivos_expurgo via API e remove o registro
da tabela processos_arquivos.

Executada automaticamente pelo daemon a cada ciclo configurado (padrão: 1×/hora).
"""

import logging
import os
from datetime import datetime, timezone
from typing import List

logger = logging.getLogger("automacao")

# Após quantos dias o arquivo é elegível para exclusão
DIAS_RETENCAO = 10

# Quantos ciclos do daemon passam antes de rodar o expurgo
# (daemon dorme ~20s por ciclo → 180 ciclos ≈ 1 hora)
CICLOS_ENTRE_EXPURGOS = 180


def executar_expurgo(api, dias: int = DIAS_RETENCAO) -> dict:
    """
    Busca arquivos elegíveis via API, exclui os físicos e registra no histórico.

    Args:
        api:  Instância de APIClient.
        dias: Arquivos com mais de N dias são removidos.

    Returns:
        Dict com 'removidos', 'erros', 'bytes_liberados'.
    """
    logger.info(f"[Expurgo] Verificando arquivos com mais de {dias} dias...")

    elegiveis: List[dict] = api.listar_elegiveis_expurgo(dias)
    if not elegiveis:
        logger.debug("[Expurgo] Nenhum arquivo elegível para exclusão.")
        return {"removidos": 0, "erros": 0, "bytes_liberados": 0}

    logger.info(f"[Expurgo] {len(elegiveis)} arquivo(s) elegível(is) encontrado(s).")

    removidos   = 0
    erros       = 0
    bytes_lib   = 0

    for arq in elegiveis:
        caminho = arq.get("caminho_arquivo", "")
        nome    = arq.get("nome_arquivo") or os.path.basename(caminho)

        # Tenta excluir o arquivo físico
        if caminho and os.path.isfile(caminho):
            try:
                os.remove(caminho)
                logger.info(f"[Expurgo] Arquivo removido: {nome}")
            except OSError as e:
                logger.warning(f"[Expurgo] Falha ao remover '{caminho}': {e}")
                erros += 1
                continue
        elif caminho and not os.path.isfile(caminho):
            # Arquivo já não existe no disco — registra mesmo assim
            logger.debug(f"[Expurgo] Arquivo não encontrado no disco (já removido?): {caminho}")

        # Registra no histórico via API (inclui motivo para rastreabilidade)
        payload = dict(arq)
        payload["motivo"] = f"expurgo_automatico_{dias}d"
        ok = api.registrar_expurgo(payload)
        if ok:
            bytes_lib += int(arq.get("tamanho_bytes") or 0)
            removidos += 1
        else:
            logger.warning(f"[Expurgo] Falha ao registrar expurgo na API para: {nome}")
            erros += 1

    logger.info(
        f"[Expurgo] Concluído — removidos: {removidos}, "
        f"erros: {erros}, "
        f"liberados: {_fmt_bytes(bytes_lib)}"
    )
    return {"removidos": removidos, "erros": erros, "bytes_liberados": bytes_lib}


def _fmt_bytes(b: int) -> str:
    if b >= 1_073_741_824: return f"{b/1_073_741_824:.2f} GB"
    if b >= 1_048_576:     return f"{b/1_048_576:.2f} MB"
    if b >= 1_024:         return f"{b/1_024:.2f} KB"
    return f"{b} B"

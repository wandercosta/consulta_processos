<?php

interface RobotRepositoryInterface
{
    /** Retorna a linha de configuração do daemon (sempre existe — id=1) */
    public function getConfig(): array;

    /** Liga ou desliga o daemon via painel */
    public function setAtivo(bool $ativo): void;

    /** Atualizado pelo daemon a cada ciclo para indicar que está vivo */
    public function updateHeartbeat(string $status, ?int $pid, ?string $mensagem): void;
}

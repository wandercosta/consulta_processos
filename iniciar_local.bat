@echo off
setlocal EnableDelayedExpansion
title Processos API — Inicialização Local
color 0A

echo.
echo  ============================================
echo   PROCESSOS API — Inicialização Local
echo  ============================================
echo.

:: ── 1. Verificar privilégios de administrador (necessário para sc start) ──────
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo  [AVISO] Executando sem privilégios de Administrador.
    echo          O start dos serviços WAMP pode falhar.
    echo          Clique direito no .bat e escolha "Executar como administrador".
    echo.
    pause
)

:: ── 2. Apache ──────────────────────────────────────────────────────────────────
sc query wampapache64 | find "RUNNING" >nul 2>&1
if %errorlevel% equ 0 (
    echo  [OK] Apache já está rodando.
) else (
    echo  [--] Iniciando Apache...
    sc start wampapache64 >nul 2>&1
    timeout /t 3 /nobreak >nul
    sc query wampapache64 | find "RUNNING" >nul 2>&1
    if %errorlevel% equ 0 (
        echo  [OK] Apache iniciado com sucesso.
    ) else (
        echo  [ERRO] Falha ao iniciar o Apache. Verifique o WAMP.
    )
)

:: ── 3. MySQL ───────────────────────────────────────────────────────────────────
sc query wampmysqld64 | find "RUNNING" >nul 2>&1
if %errorlevel% equ 0 (
    echo  [OK] MySQL já está rodando.
) else (
    echo  [--] Iniciando MySQL...
    sc start wampmysqld64 >nul 2>&1
    timeout /t 4 /nobreak >nul
    sc query wampmysqld64 | find "RUNNING" >nul 2>&1
    if %errorlevel% equ 0 (
        echo  [OK] MySQL iniciado com sucesso.
    ) else (
        echo  [ERRO] Falha ao iniciar o MySQL. Verifique o WAMP.
    )
)

echo.

:: ── 4. Testar API ──────────────────────────────────────────────────────────────
echo  [--] Testando API em http://localhost/processos_api/api/?endpoint=robot_status ...
curl -s -o nul -w "%%{http_code}" -H "Authorization: Bearer CLAUDE_AUTOMACAO_123" ^
    "http://localhost/processos_api/api/?endpoint=robot_status" > "%TEMP%\api_check.txt" 2>nul
set /p HTTP_CODE=<"%TEMP%\api_check.txt"
if "%HTTP_CODE%"=="200" (
    echo  [OK] API respondendo ^(HTTP 200^).
) else (
    echo  [AVISO] API retornou HTTP %HTTP_CODE%. Verifique se o WAMP está com a URL correta.
)

echo.

:: ── 5. Abrir painel no browser ─────────────────────────────────────────────────
echo  [--] Abrindo painel em http://localhost/processos_api/painel/ ...
start "" "http://localhost/processos_api/painel/"

echo.

:: ── 6. Perguntar se quer iniciar o Robô Python ────────────────────────────────
echo  ============================================
echo   Deseja iniciar o Robô Python agora?
echo  ============================================
echo.
echo   [1] Sim — modo LOOP (headless, reinicia automaticamente)
echo   [2] Sim — modo SIMPLES (executa uma vez, com navegador visível)
echo   [3] Não — apenas subir os serviços e abrir o painel
echo.
set /p OPCAO="  Escolha (1/2/3): "

if "%OPCAO%"=="1" (
    echo.
    echo  [--] Iniciando robô em modo LOOP headless em nova janela...
    start "Robo Python — Loop Headless" cmd /k "cd /d "%~dp0python" && C:\Python314\python.exe main.py --headless --loop"
    echo  [OK] Robô iniciado. Feche a janela do robô para parar.
)

if "%OPCAO%"=="2" (
    echo.
    echo  [--] Iniciando robô em modo SIMPLES em nova janela...
    start "Robo Python — Simples" cmd /k "cd /d "%~dp0python" && C:\Python314\python.exe main.py"
    echo  [OK] Robô iniciado.
)

if "%OPCAO%"=="3" (
    echo.
    echo  [OK] Serviços ativos. Robô não iniciado.
)

echo.
echo  ============================================
echo   Ambiente local pronto!
echo   Painel:  http://localhost/processos_api/painel/
echo   API:     http://localhost/processos_api/api/
echo  ============================================
echo.
pause

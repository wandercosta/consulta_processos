@echo off
setlocal EnableDelayedExpansion
chcp 65001 >nul 2>&1
title Processos API — Inicializando...

echo.
echo  ██████╗ ██████╗  ██████╗  ██████╗███████╗███████╗███████╗ ██████╗ ███████╗
echo  ██╔══██╗██╔══██╗██╔═══██╗██╔════╝██╔════╝██╔════╝██╔════╝██╔═══██╗██╔════╝
echo  ██████╔╝██████╔╝██║   ██║██║     █████╗  ███████╗███████╗██║   ██║███████╗
echo  ██╔═══╝ ██╔══██╗██║   ██║██║     ██╔══╝  ╚════██║╚════██║██║   ██║╚════██║
echo  ██║     ██║  ██║╚██████╔╝╚██████╗███████╗███████║███████║╚██████╔╝███████║
echo  ╚═╝     ╚═╝  ╚═╝ ╚═════╝  ╚═════╝╚══════╝╚══════╝╚══════╝ ╚═════╝ ╚══════╝
echo.
echo  Consulta e gestao de processos juridicos — Ambiente Local
echo  ──────────────────────────────────────────────────────────
echo.

:: ─── Diretório raiz do projeto ────────────────────────────────────────────────
cd /d "%~dp0"

:: ─── 1. Localizar PHP 8.x ─────────────────────────────────────────────────────
set PHP=
if exist "E:\Develop\php\php8.4.15\php.exe" ( set PHP=E:\Develop\php\php8.4.15\php.exe & goto :php_ok )
if exist "E:\Develop\php\php8.3.28\php.exe" ( set PHP=E:\Develop\php\php8.3.28\php.exe & goto :php_ok )
if exist "E:\Develop\php\php8.2.29\php.exe" ( set PHP=E:\Develop\php\php8.2.29\php.exe & goto :php_ok )
if exist "E:\Develop\php\php8.1.33\php.exe" ( set PHP=E:\Develop\php\php8.1.33\php.exe & goto :php_ok )
if exist "E:\wamp64\bin\php\php8.2.18\php.exe" ( set PHP=E:\wamp64\bin\php\php8.2.18\php.exe & goto :php_ok )
where php >nul 2>&1 && ( for /f "delims=" %%i in ('where php') do set PHP=%%i & goto :php_ok )

echo  [ERRO] PHP 8.x nao encontrado. Verifique E:\Develop\php\ ou adicione ao PATH.
pause & exit /b 1

:php_ok
echo  [OK] PHP: %PHP%

:: ─── 2. Localizar Python ──────────────────────────────────────────────────────
set PYTHON=
if exist "C:\Python314\python.exe" ( set PYTHON=C:\Python314\python.exe & goto :py_ok )
if exist "C:\Python313\python.exe" ( set PYTHON=C:\Python313\python.exe & goto :py_ok )
if exist "C:\Python312\python.exe" ( set PYTHON=C:\Python312\python.exe & goto :py_ok )
where python >nul 2>&1 && ( for /f "delims=" %%i in ('where python') do set PYTHON=%%i & goto :py_ok )
set PYTHON=python

:py_ok
echo  [OK] Python: %PYTHON%

:: ─── 3. MySQL (serviço WAMP — sem Apache) ────────────────────────────────────
sc query wampmysqld64 | find "RUNNING" >nul 2>&1
if %errorlevel% equ 0 (
    echo  [OK] MySQL ja esta rodando.
) else (
    echo  [--] Iniciando MySQL...
    sc start wampmysqld64 >nul 2>&1
    timeout /t 4 /nobreak >nul
    sc query wampmysqld64 | find "RUNNING" >nul 2>&1
    if %errorlevel% equ 0 (
        echo  [OK] MySQL iniciado.
    ) else (
        echo  [AVISO] Nao foi possivel iniciar o MySQL automaticamente.
        echo          Inicie manualmente: sc start wampmysqld64 ^(como Administrador^)
    )
)

:: ─── 4. Configurar .env para servidor embutido ────────────────────────────────
set PORT=8000

if not exist ".env" (
    echo  [INFO] .env nao encontrado — copiando de .env.example...
    copy ".env.example" ".env" >nul
)

:: Ajusta APP_BASE_PATH e API_BASE_URL para o servidor embutido na porta 8000
echo  [--] Ajustando .env para servidor embutido ^(porta %PORT%^)...
set "ENV_TEMP=%TEMP%\processos_env_tmp.txt"

(for /f "usebackq delims=" %%L in (".env") do (
    set "LINE=%%L"
    if "!LINE:~0,13!"=="APP_BASE_PATH" (
        echo APP_BASE_PATH=
    ) else if "!LINE:~0,12!"=="API_BASE_URL" (
        echo API_BASE_URL=http://localhost:%PORT%/api
    ) else (
        echo !LINE!
    )
)) > "%ENV_TEMP%"

copy /y "%ENV_TEMP%" ".env" >nul
echo  [OK] .env configurado ^(APP_BASE_PATH vazio, API_BASE_URL porta %PORT%^).

echo.
echo  ──────────────────────────────────────────────────────────
echo   Iniciando servicos em janelas separadas...
echo   PHP Server  ^>  http://localhost:%PORT%/
echo   Painel      ^>  http://localhost:%PORT%/painel/
echo  ──────────────────────────────────────────────────────────
echo.

:: ─── 5. Iniciar PHP built-in server ──────────────────────────────────────────
start "Processos API — PHP Server :%PORT%" cmd /k "color 0B && title Processos API — PHP Server :%PORT% && \"%PHP%\" -S localhost:%PORT% -t \"%~dp0\""
timeout /t 2 /nobreak >nul

:: ─── 6. Abrir painel no browser ───────────────────────────────────────────────
echo  [--] Abrindo painel no navegador...
start "" "http://localhost:%PORT%/painel/"

echo.

:: ─── 7. Robô Python (opcional) ────────────────────────────────────────────────
echo  ──────────────────────────────────────────────────────────
echo   Deseja iniciar o Robo Python?
echo  ──────────────────────────────────────────────────────────
echo.
echo   [1] Sim — modo LOOP headless ^(reinicia automaticamente^)
echo   [2] Sim — modo SIMPLES ^(uma execucao, navegador visivel^)
echo   [3] Nao
echo.
set /p OPCAO="  Escolha (1/2/3): "

if "%OPCAO%"=="1" (
    echo.
    start "Processos API — Robo Loop" cmd /k "color 0A && title Processos API — Robo Loop && cd /d \"%~dp0python\" && \"%PYTHON%\" main.py --headless --loop"
    echo  [OK] Robo iniciado em modo LOOP.
)
if "%OPCAO%"=="2" (
    echo.
    start "Processos API — Robo Simples" cmd /k "color 06 && title Processos API — Robo Simples && cd /d \"%~dp0python\" && \"%PYTHON%\" main.py"
    echo  [OK] Robo iniciado em modo SIMPLES.
)

echo.
echo  ══════════════════════════════════════════════════════════
echo   Ambiente local pronto! Janelas abertas:
echo   [azul]   PHP Built-in Server — porta %PORT%
if "%OPCAO%"=="1" echo   [verde]  Robo Python — loop headless
if "%OPCAO%"=="2" echo   [amarelo] Robo Python — simples
echo.
echo   Painel:  http://localhost:%PORT%/painel/
echo   API:     http://localhost:%PORT%/api/
echo  ══════════════════════════════════════════════════════════
echo.
echo  Para parar: feche as janelas coloridas abertas.
echo.
pause

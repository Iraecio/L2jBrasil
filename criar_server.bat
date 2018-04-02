@echo off

SET mypath=%~dp0

echo %mypath%

REM ###############################################
REM ##    Configuração para montar servidor!     ##
REM ###############################################
set CoreBuildPath=%mypath%/L2JBrasil_CORE/build/dist
set DpBuildPath=%mypath%/L2JBrasil_DP/build/dist

set DestServerPath=%mypath%servidor/2

:Config1
cls
echo. ---------------------------------------------------------------------
echo.           CONFIGURANDO O ACESSO AO MYSQL
echo. ---------------------------------------------------------------------

set Step1prompt0=x
set /p Step1prompt0= Mysql User:
if /i not "%Step1prompt0%"==" " goto MysqlUser

:MysqlUser
setLocal EnableDelayedExpansion
cd %DestServerPath%/Game/config
set InputFile=server.properties
set OutputFile=server2.properties
set _strFind= "Login = root"
set _strInsert= "Login = %Step1prompt0%"

"%OutputFile%" (
set /a count=0
  for /f "usebackq delims=" %%A in ("%InputFile%") do (
    	set /a count+=1
        echo %%A - !count!

  )
  pause
)
ENDLOCAL
goto :Config1

:Step1
cls
echo. ---------------------------------------------------------------------
echo.   1 - Remover as configs.
echo.   2 - Manter as configs.
echo.   3 - Sair
echo. ---------------------------------------------------------------------

set Step1prompt=x
set /p Step1prompt= O que deseja fazer:
if /i %Step1prompt%==1 goto FullInstall
if /i %Step1prompt%==2 goto OnlyFilesNoConfig
if /i %Step1prompt%==3 goto end
goto Step1

:Step2
cls
echo. ---------------------------------------------------------------------
echo.                       Iniciar o servidor.
echo. ---------------------------------------------------------------------

set Step1prompt2=x
set /p Step1prompt2= Deseja iniciar o servidor e o login? (s/n) | Ou 3 para sair:
if /i %Step1prompt2%==s goto IniciarServer
if /i %Step1prompt2%==3 goto end
goto Step2

:IniciarServer
echo Iniciando o GameServer
cd "%DestServerPath%/Game/"
call %DestServerPath%/Game/Game.bat
echo Iniciando o LoginServer
cd "%DestServerPath%/Login/"
call %DestServerPath%/Login/Login.bat
pause
goto Step2

REM ###############################################
REM ##          nao mecher abaixo!               ##
:FullInstall
echo Removendo arquivos anteriores
REM IF EXIST "%DestServerPath%/Database" (
REM     rd /q /s "%DestServerPath%/Database/"
REM     IF EXIST "%DestServerPath%/Game" (
REM         rd /q /s "%DestServerPath%/Game/"
REM     )
REM     IF EXIST "%DestServerPath%/Login" (
REM         rd /q /s "%DestServerPath%/Login/"
REM     )
REM )

REM echo Copiando arquivos dos gameserver
REM robocopy %CoreBuildPath% %DestServerPath% /s /e /NFL /NDL /NJH /NJS
REM echo Copiando arquivos dos Datapack
REM robocopy %DpBuildPath% %DestServerPath% /s /e /NFL /NDL /NJH /NJS

echo Fazendo registro do Banco e Servidor
cd "%DestServerPath%/Login/"
call %DestServerPath%/Login/Register.bat
pause
exit
echo servidor montado com sucesso!
pause
goto :Step1

:OnlyFilesNoConfig
setlocal enableextensions enabledelayedexpansion
IF EXIST "%DestServerPath%/Database" (
    echo Removendo %DestServerPath%/Database/...
    rd /q /s "%DestServerPath%/Database/"
)

IF EXIST "%DestServerPath%/Login/" (
    echo Removendo %DestServerPath%/Login
    cd "%DestServerPath%/Login/"
    for /f %%G in ('dir /a-d "%DestServerPath%\Login\" /b') do if /i not "%%G"=="config" del "%%G"
    echo Login files Removidos.
    del /s /q "log"
    echo Login log Removido.
    rmdir "log"
)

IF EXIST "%DestServerPath%/Game/" (
    echo Removendo %DestServerPath%/Game/...
    cd "%DestServerPath%/Game/"
    for /f %%G in ('dir /a-d "%DestServerPath%\Game\" /b') do (
        if /i not "%%G"=="config" (
            echo Removendo "%%G"
            del "%%G"
        )
    )
    for /d %%G in ("%DestServerPath%\Game\*") do (
        if /i not "%%~nxG"=="config" (
            echo Removendo "%%~nxG"
            del /s /q "%%~nxG"
        )
    )
    echo Removido com sucesso!
)

echo Copiando arquivos dos gameserver
robocopy %CoreBuildPath% %DestServerPath% /s /e /NFL /NDL /NJH /NJS /xd config
echo Copiando arquivos dos Datapack
robocopy %DpBuildPath% %DestServerPath% /s /e /NFL /NDL /NJH /NJS /xd config
pause

:end
goto :Step1


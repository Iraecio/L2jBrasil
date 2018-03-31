@echo off

REM ###############################################
REM ##    Configuração conexão BD porfavor!      ##
REM ###############################################
REM Porfavor, digite aqui o directorio seu mysql \bin. Exemplo: C:\Program Files\MySQL\MySQL Server 6.0\bin
set mysqlBinPath=C:\Program Files\MySQL\MySQL Server 6.0\bin

set DateT=%date%

REM Configuração conexão BD loginserver
set lsuser=root
set lspass=
set lsdb=l2jdb
set lshost=localhost

REM Configuração conexão BD Gameserver
set gsuser=root
set gspass=
set gsdb=l2jdb
set gshost=localhost
REM ############################################

set mysqldumpPath="%mysqlBinPath%\mysqldump"
set mysqlPath="%mysqlBinPath%\mysql"


:Step1
cls
echo. ---------------------------------------------------------------------
echo.
echo.   L2J-Brasil - Database Login servers
echo. _____________________________________________________________________
echo.
echo.   1 - Instalacao completa database loginserver`s.
echo.   2 - Instalacao completa BD loginserver e BD gameserver
echo.   3 - Sair do instalador
echo. ---------------------------------------------------------------------

set Step1prompt=x
set /p Step1prompt= Please enter values :
if /i %Step1prompt%==1 goto LoginInstall
if /i %Step1prompt%==2 goto FullInstall
if /i %Step1prompt%==3 goto FullEnd
goto Step1


:LoginInstall

echo Limpando database : %lsdb% e instalando db loginserver`s.
echo.
%mysqlPath% -h %lshost% -u %lsuser% --password=%lspass% -D %lsdb% < login_install.sql
echo Atualizando tabela accounts.sql
%mysqlPath% -h %lshost% -u %lsuser% --password=%lspass% -D %lsdb% < ../Database/sql/accounts.sql
echo Atualizando tabela gameservers.sql
%mysqlPath% -h %lshost% -u %lsuser% --password=%lspass% -D %lsdb% < ../Database/sql/gameservers.sql
echo.
echo O DB do LOGINSERVER foi instalado com sucesso!!
pause
goto :Step1


:FullInstall
setlocal enableextensions enabledelayedexpansion

echo Removendo o BD GAMESERVER database`s.
%mysqlPath% -h %gshost% -u %gsuser% --password=%gspass% -D %gsdb% < full_install.sql
echo.
echo Instalando o BD GAMESERVER.
echo.
set /a count=0
for /f %%G in ('dir /a-d sql\*.sql /b') do (
	set /a count+=1
	echo *** !count! % Instalado com sucesso. **
	IF NOT %%~G == accounts.sql (
	    IF NOT %%~G == gameservers.sql (
	        %mysqlPath% -h %gshost% -u %gsuser% --password=%gspass% -D %gsdb% < ../Database/sql/%%~G
	    )
	)
)

echo *** Sucesfull 100 percents. **
echo.
echo GameServer Database Instalado com sucesso.
pause
goto :Step1

:FullEnd

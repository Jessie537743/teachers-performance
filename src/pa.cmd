@echo off
REM Run Laravel Artisan with XAMPP PHP 8.4+ (avoids WAMP/other PHP 7.x on PATH).
setlocal
set "ROOT=%~dp0"
set "PHP_EXE="
if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
if defined PHP_EXE (
  "%PHP_EXE%" "%ROOT%artisan" %*
) else (
  php "%ROOT%artisan" %*
)
exit /b %ERRORLEVEL%

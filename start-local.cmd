@echo off
cd /d "%~dp0public"
"C:\php-8.3.31\php.exe" -c "%~dp0.local\php.ini" -d curl.cainfo="%~dp0vendor\composer\ca-bundle\res\cacert.pem" -d openssl.cafile="%~dp0vendor\composer\ca-bundle\res\cacert.pem" -S 127.0.0.1:8000 "%~dp0vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php"

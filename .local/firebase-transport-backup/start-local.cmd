@echo off
cd /d "%~dp0public"
"C:\php-8.3.31\php.exe" -c "%~dp0.local\php.ini" -S 127.0.0.1:8086 "%~dp0vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php"

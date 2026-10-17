@echo off
set "PHPRC=%~dp0.local\php.ini"
set "COMPOSER_CACHE_DIR=%~dp0.local\composer-cache"
"C:\php-8.3.31\php.exe" -c "%PHPRC%" "C:\Users\Satyajeet\.config\herd-lite\bin\composer.phar" %*

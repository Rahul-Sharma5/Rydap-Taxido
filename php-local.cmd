@echo off
"C:\php-8.3.31\php.exe" -c "%~dp0.local\php.ini" -d curl.cainfo="%~dp0vendor\composer\ca-bundle\res\cacert.pem" -d openssl.cafile="%~dp0vendor\composer\ca-bundle\res\cacert.pem" %*

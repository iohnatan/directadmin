@REM to not print commands.
@ECHO OFF

@REM composer update: upgrade our project packages (development).
@REM composer install: install the same dependencies stored in the composer.lock (production).
@REM --no-dev: only install dependencies which are required for running the application.
%COMPOSER74% update --no-dev
@echo off
REM ============================================================
REM  Centralized Migration Runner
REM  Run this script to migrate all tables to the shared database
REM ============================================================
REM
REM Usage:
REM   migrate.bat              - Run migrations
REM   migrate.bat --seed       - Run migrations with seeders
REM   migrate.bat --fresh      - Fresh migration (drop all & re-migrate)
REM   migrate.bat --fresh --seed - Fresh migration with seeders
REM   migrate.bat --rollback   - Rollback last batch
REM

echo.
echo ========================================
echo  Microservices - Central Migration
echo ========================================
echo.

REM Use auth-service as the migration host (it has the DB connection)
set SERVICE_DIR=%~dp0auth-service

REM Check if auth-service exists
if not exist "%SERVICE_DIR%" (
    echo ERROR: auth-service directory not found!
    echo Please run this script from the project root directory.
    exit /b 1
)

REM Determine migration path (relative to auth-service)
set MIGRATION_PATH=../database/migrations

echo Using service: auth-service
echo Migration path: %~dp0database\migrations (relative: %MIGRATION_PATH%)
echo.

if "%1"=="--fresh" (
    if "%2"=="--seed" (
        echo Running: php artisan migrate:fresh --path --seed
        cd /d "%SERVICE_DIR%"
        php artisan migrate:fresh --path="%MIGRATION_PATH%" --seed --seeder="Database\Seeders\DatabaseSeeder"
    ) else (
        echo Running: php artisan migrate:fresh --path
        cd /d "%SERVICE_DIR%"
        php artisan migrate:fresh --path="%MIGRATION_PATH%"
    )
) else if "%1"=="--seed" (
    echo Running: php artisan migrate --path + db:seed
    cd /d "%SERVICE_DIR%"
    php artisan migrate --path="%MIGRATION_PATH%"
    php artisan db:seed --seeder="Database\Seeders\DatabaseSeeder"
) else if "%1"=="--rollback" (
    echo Running: php artisan migrate:rollback --path
    cd /d "%SERVICE_DIR%"
    php artisan migrate:rollback --path="%MIGRATION_PATH%"
) else (
    echo Running: php artisan migrate --path
    cd /d "%SERVICE_DIR%"
    php artisan migrate --path="%MIGRATION_PATH%"
)

echo.
echo ========================================
echo  Migration completed!
echo ========================================

@echo off
REM ============================================================
REM  Start All Microservices
REM  Opens separate terminal windows for each service
REM ============================================================

echo.
echo ========================================
echo  Starting All Microservices
echo ========================================
echo.

echo Starting Auth Service on port 8001...
start "Auth Service (8001)" cmd /k "cd /d %~dp0auth-service && set PHP_CLI_SERVER_WORKERS=5 && php artisan serve --port=8001 --no-reload"

ping 127.0.0.1 -n 3 >nul

echo Starting Property Service on port 8002...
start "Property Service (8002)" cmd /k "cd /d %~dp0property-service && set PHP_CLI_SERVER_WORKERS=5 && php artisan serve --port=8002 --no-reload"

ping 127.0.0.1 -n 3 >nul

echo Starting Payment Service on port 8003...
start "Payment Service (8003)" cmd /k "cd /d %~dp0payment-service && set PHP_CLI_SERVER_WORKERS=5 && php artisan serve --port=8003 --no-reload"

ping 127.0.0.1 -n 3 >nul

echo Starting Complaint Service on port 8005...
start "Complaint Service (8005)" cmd /k "cd /d %~dp0complaint-service && set PHP_CLI_SERVER_WORKERS=5 && php artisan serve --port=8005 --no-reload"

ping 127.0.0.1 -n 3 >nul

echo Starting Notification Service on port 8007...
start "Notification Service (8007)" cmd /k "cd /d %~dp0notification-service && set PHP_CLI_SERVER_WORKERS=5 && php artisan serve --port=8007 --no-reload"

ping 127.0.0.1 -n 3 >nul

echo Starting API Gateway on port 8000...
start "API Gateway (8000)" cmd /k "cd /d %~dp0api-gateway && set PHP_CLI_SERVER_WORKERS=5 && php artisan serve --port=8000 --no-reload"

echo.
echo ========================================
echo  All services started!
echo ========================================
echo.
echo  API Gateway:            http://localhost:8000
echo  Auth Service:           http://localhost:8001
echo  Property Service:       http://localhost:8002
echo  Payment Service:        http://localhost:8003
echo  Complaint Service:      http://localhost:8005
echo  Notification Service:   http://localhost:8007
echo.
echo  Use the API Gateway (port 8000) for all client requests.
echo ========================================

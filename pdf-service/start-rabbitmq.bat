@echo off
REM Start PDF Service with RabbitMQ
REM This script starts the Node.js PDF service that listens on RabbitMQ queue

echo ========================================
echo   Starting PDF Service (RabbitMQ)
echo ========================================
echo.

cd /d "%~dp0"

REM Check if node_modules exists
if not exist "node_modules\" (
    echo [ERROR] node_modules not found!
    echo [INFO] Please run: npm install
    echo.
    pause
    exit /b 1
)

REM Check if amqplib is installed
if not exist "node_modules\amqplib\" (
    echo [ERROR] amqplib package not found!
    echo [INFO] Please run: npm install
    echo.
    pause
    exit /b 1
)

echo [INFO] Starting RabbitMQ PDF Service...
echo [INFO] Queue: pdf_generation_requests
echo [INFO] Press CTRL+C to stop
echo.

node server-rabbitmq.js

echo.
echo ========================================
echo   Service Stopped
echo ========================================
pause

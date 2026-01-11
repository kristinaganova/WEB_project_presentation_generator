@echo off
REM Quick Start Script for PDF Microservice (Windows)

echo.
echo ========================================
echo    PDF Microservice - Quick Start
echo ========================================
echo.

REM Check Node.js
where node >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: Node.js not found!
    echo Please install Node.js from: https://nodejs.org/
    echo.
    pause
    exit /b 1
)

echo [OK] Node.js found
node --version

REM Check npm
where npm >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: npm not found!
    pause
    exit /b 1
)

echo [OK] npm found
npm --version
echo.

REM Check if package.json exists
if not exist "package.json" (
    echo ERROR: package.json not found!
    echo Make sure you're in the pdf-service folder
    pause
    exit /b 1
)

echo [OK] package.json found
echo.

REM Check if node_modules exists
if not exist "node_modules" (
    echo Installing dependencies...
    echo This may take 2-3 minutes...
    echo.
    call npm install
    echo.
    echo Dependencies installed!
    echo.
)

REM Start the service
echo ========================================
echo    Starting PDF Microservice...
echo ========================================
echo.
echo Service will run on: http://localhost:3001
echo Press Ctrl+C to stop
echo.

npm start

# Presentation Generator - Quick Start Script
# Starts all three services in separate windows

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Presentation Generator - Starting..." -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Check if services are already running
$pdfRunning = Test-NetConnection -ComputerName localhost -Port 3001 -InformationLevel Quiet -WarningAction SilentlyContinue
$wsRunning = Test-NetConnection -ComputerName localhost -Port 3002 -InformationLevel Quiet -WarningAction SilentlyContinue

if ($pdfRunning) {
    Write-Host "[!] PDF Service already running on port 3001" -ForegroundColor Yellow
} else {
    Write-Host "[1/3] Starting PDF Microservice (port 3001)..." -ForegroundColor Green
    Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PSScriptRoot\pdf-service'; Write-Host 'PDF Microservice' -ForegroundColor Cyan; npm start"
    Start-Sleep -Seconds 2
}

if ($wsRunning) {
    Write-Host "[!] WebSocket Service already running on port 3002" -ForegroundColor Yellow
} else {
    Write-Host "[2/3] Starting WebSocket Service (port 3002)..." -ForegroundColor Green
    Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PSScriptRoot\websocket-service'; Write-Host 'WebSocket Service' -ForegroundColor Cyan; npm start"
    Start-Sleep -Seconds 2
}

Write-Host "[3/3] Starting PHP Application (port 8000)..." -ForegroundColor Green
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PSScriptRoot'; Write-Host 'PHP Application Server' -ForegroundColor Cyan; php -S localhost:8000 -t public"

Start-Sleep -Seconds 3

Write-Host ""
Write-Host "============================================" -ForegroundColor Green
Write-Host "  All Services Started!" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Green
Write-Host ""
Write-Host "Access Points:" -ForegroundColor Cyan
Write-Host "  • Application:      http://localhost:8000" -ForegroundColor White
Write-Host "  • API Docs:         http://localhost:8000/api-docs.html" -ForegroundColor White
Write-Host "  • PDF Service:      http://localhost:3001/health" -ForegroundColor White
Write-Host "  • WebSocket:        http://localhost:3003/health" -ForegroundColor White
Write-Host ""
Write-Host "Press any key to open application in browser..." -ForegroundColor Yellow
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

Start-Process "http://localhost:8000"
Start-Process "http://localhost:8000/api-docs.html"

Write-Host ""
Write-Host "Services are running in separate windows." -ForegroundColor Green
Write-Host "Close those windows to stop the services." -ForegroundColor Yellow
Write-Host ""

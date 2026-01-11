# PDF Microservice - Quick Setup Script
# Automates installation and testing of Node.js PDF generation microservice

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "   PDF Microservice Setup - Task 3" -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

# Check if Node.js is installed
Write-Host "🔍 Checking Node.js installation..." -ForegroundColor Yellow
try {
    $nodeVersion = node --version
    Write-Host "✅ Node.js found: $nodeVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ Node.js not found!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please install Node.js from: https://nodejs.org/" -ForegroundColor Yellow
    Write-Host "Recommended: Node.js 18 LTS or newer" -ForegroundColor Yellow
    Write-Host ""
    pause
    exit 1
}

# Check if npm is installed
try {
    $npmVersion = npm --version
    Write-Host "✅ npm found: v$npmVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ npm not found!" -ForegroundColor Red
    exit 1
}

Write-Host ""

# Navigate to pdf-service directory
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$pdfServicePath = Join-Path $scriptPath "pdf-service"

if (-not (Test-Path $pdfServicePath)) {
    Write-Host "❌ pdf-service folder not found!" -ForegroundColor Red
    Write-Host "Expected location: $pdfServicePath" -ForegroundColor Yellow
    exit 1
}

Write-Host "📁 Navigating to pdf-service folder..." -ForegroundColor Yellow
Set-Location $pdfServicePath

# Check if package.json exists
if (-not (Test-Path "package.json")) {
    Write-Host "❌ package.json not found!" -ForegroundColor Red
    exit 1
}

Write-Host "✅ Found package.json" -ForegroundColor Green
Write-Host ""

# Install dependencies
Write-Host "📦 Installing dependencies..." -ForegroundColor Yellow
Write-Host "⏱️  This may take 2-3 minutes (Puppeteer downloads Chromium ~150MB)" -ForegroundColor Cyan
Write-Host ""

try {
    npm install
    Write-Host ""
    Write-Host "✅ Dependencies installed successfully!" -ForegroundColor Green
} catch {
    Write-Host ""
    Write-Host "❌ Failed to install dependencies!" -ForegroundColor Red
    Write-Host "Try running manually: npm install" -ForegroundColor Yellow
    exit 1
}

Write-Host ""

# Ask user if they want to start the service
Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "   Setup Complete!" -ForegroundColor Green
Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

$startService = Read-Host "Do you want to start the PDF service now? (Y/N)"

if ($startService -eq "Y" -or $startService -eq "y") {
    Write-Host ""
    Write-Host "🚀 Starting PDF Microservice..." -ForegroundColor Yellow
    Write-Host "   Service will run on http://localhost:3001" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "   Press Ctrl+C to stop the service" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Cyan
    Write-Host ""
    
    # Start the service
    npm start
} else {
    Write-Host ""
    Write-Host "📋 To start the service manually:" -ForegroundColor Yellow
    Write-Host "   cd pdf-service" -ForegroundColor Cyan
    Write-Host "   npm start" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "📋 To run tests:" -ForegroundColor Yellow
    Write-Host "   cd pdf-service" -ForegroundColor Cyan
    Write-Host "   node test-service.js" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "📋 To test from browser:" -ForegroundColor Yellow
    Write-Host "   http://localhost:3001/health" -ForegroundColor Cyan
    Write-Host ""
}

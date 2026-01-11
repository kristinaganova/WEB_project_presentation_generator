# PowerShell script to run API tests
# Usage: .\run-tests.ps1

Write-Host "╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║         API Test Runner - Presentation Generator               ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Check if PHP is available
$phpPath = Get-Command php -ErrorAction SilentlyContinue

if (-not $phpPath) {
    Write-Host "❌ PHP not found in PATH" -ForegroundColor Red
    Write-Host "   Please install PHP or add it to your system PATH" -ForegroundColor Yellow
    Write-Host ""
    exit 1
}

Write-Host "✅ PHP found: $($phpPath.Source)" -ForegroundColor Green
Write-Host ""

# Change to project directory
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectRoot = Split-Path -Parent $scriptPath
Set-Location $projectRoot

# Check if test file exists
if (-not (Test-Path "tests\ApiTest.php")) {
    Write-Host "❌ Test file not found: tests\ApiTest.php" -ForegroundColor Red
    exit 1
}

# Ask if user wants to provide session cookie
Write-Host "Do you want to run full tests with authentication? (Y/N)" -ForegroundColor Yellow
Write-Host "  Y - Full tests (requires login session)" -ForegroundColor Gray
Write-Host "  N - Basic tests only (health check, errors)" -ForegroundColor Gray
Write-Host ""

$choice = Read-Host "Your choice"

if ($choice -eq "Y" -or $choice -eq "y") {
    Write-Host ""
    Write-Host "Please provide your session cookie:" -ForegroundColor Yellow
    Write-Host "  1. Login to the application in your browser" -ForegroundColor Gray
    Write-Host "  2. Open DevTools (F12) → Application → Cookies" -ForegroundColor Gray
    Write-Host "  3. Copy the PHPSESSID value" -ForegroundColor Gray
    Write-Host ""
    
    $sessionCookie = Read-Host "Enter PHPSESSID value (or press Enter to skip)"
    
    if ($sessionCookie) {
        Write-Host ""
        Write-Host "🚀 Running full test suite with authentication..." -ForegroundColor Cyan
        Write-Host ""
        php tests\ApiTest.php "PHPSESSID=$sessionCookie"
    } else {
        Write-Host ""
        Write-Host "🚀 Running basic tests without authentication..." -ForegroundColor Cyan
        Write-Host ""
        php tests\ApiTest.php
    }
} else {
    Write-Host ""
    Write-Host "🚀 Running basic tests without authentication..." -ForegroundColor Cyan
    Write-Host ""
    php tests\ApiTest.php
}

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

# Check exit code
if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Tests completed successfully!" -ForegroundColor Green
} else {
    Write-Host "⚠️  Tests completed with warnings" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Press any key to exit..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

# Binest APK Builder Script
$ErrorActionPreference = "Stop"

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "   Binest APK Builder" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# Set location
$projectPath = "C:\xampp\htdocs\binest\binest"
Set-Location $projectPath

# Check Node
Write-Host "[1/4] Checking Node.js..." -ForegroundColor Yellow
try {
    $nodeVersion = node --version
    Write-Host "    Found: $nodeVersion" -ForegroundColor Green
} catch {
    Write-Host "    ERROR: Node.js not found!" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

# Install dependencies
Write-Host ""
Write-Host "[2/4] Installing dependencies..." -ForegroundColor Yellow
try {
    npm install 2>&1 | ForEach-Object { Write-Host "    $_" -ForegroundColor Gray }
    Write-Host "    Dependencies installed!" -ForegroundColor Green
} catch {
    Write-Host "    ERROR: npm install failed!" -ForegroundColor Red
    Write-Host "    $_" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

# Check EAS
Write-Host ""
Write-Host "[3/4] Checking EAS CLI..." -ForegroundColor Yellow
try {
    $easVersion = npx eas-cli --version 2>&1 | Select-Object -First 1
    Write-Host "    Found: $easVersion" -ForegroundColor Green
} catch {
    Write-Host "    Installing EAS CLI..." -ForegroundColor Yellow
    npm install -g eas-cli 2>&1 | ForEach-Object { Write-Host "    $_" -ForegroundColor Gray }
}

# Start build
Write-Host ""
Write-Host "[4/4] Starting APK Build..." -ForegroundColor Yellow
Write-Host "    This will open a browser to login to Expo..." -ForegroundColor Cyan
Write-Host "    Build will be done in the cloud." -ForegroundColor Cyan
Write-Host ""

Write-Host "Press Enter to start build..." -ForegroundColor Green
Read-Host

try {
    npx eas-cli build --platform android --profile preview
} catch {
    Write-Host ""
    Write-Host "==========================================" -ForegroundColor Red
    Write-Host "Build command failed!" -ForegroundColor Red
    Write-Host "==========================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "Try this manual command:"
    Write-Host "cd C:\xampp\htdocs\binest\binest"
    Write-Host "npx eas-cli build --platform android --profile preview"
    Write-Host ""
}

Write-Host ""
Write-Host "==========================================" -ForegroundColor Green
Write-Host "Build process initiated!" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Check your email or visit:"
Write-Host "https://expo.dev/builds" -ForegroundColor Cyan
Write-Host ""
Read-Host "Press Enter to exit"

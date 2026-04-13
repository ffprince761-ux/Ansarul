@echo off
chcp 65001 >nul
title Binest APK Builder
color 0A

:: Clear screen and show header
cls
echo.
echo  ============================================
echo     Binest Mobile App - APK Builder
echo  ============================================
echo.
echo  API URL: https://tensemock.in/api
echo  Status: Ready to build
echo.

:: Check if node exists at expected location
if not exist "D:\node.exe" (
    color 0C
    echo  [ERROR] Node.js not found at D:\node.exe
    echo.
    echo  Node.js install karo ya path check karo:
    echo  https://nodejs.org/download/
    echo.
    pause
    exit /b 1
)

echo  [OK] Node.js found
echo.

:: Change to project directory
cd /d "C:\xampp\htdocs\binest\binest"
echo  Project: %CD%
echo.

:: Run npm install using explicit paths
echo  [1/3] Installing dependencies...
echo.
"D:\node.exe" "D:\node_modules\npm\bin\npm-cli.js" install --silent

if %errorlevel% neq 0 (
    echo  [WARNING] Install had issues, continuing anyway...
)

echo.
echo  [2/3] Starting EAS Build...
echo.
echo  ----------------------------------------
echo  Browser open hoga - Expo mein login karo
echo  ----------------------------------------
echo.

:: Start the EAS build
"D:\node.exe" "D:\node_modules\npm\bin\npx-cli.js" eas-cli build --platform android --profile preview --non-interactive 2>&1

echo.
echo  ============================================
echo  Build process initiated!
echo  ============================================
echo.
echo  Status check karo:
echo  https://expo.dev/builds
echo.
echo  Ya email check karo
echo.

pause

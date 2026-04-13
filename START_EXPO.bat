@echo off
title Expo Server - Binest App
color 0C
cls

echo.
echo  ╔══════════════════════════════════════════════════════════════╗
echo  ║                    EXPO SERVER START                        ║
echo  ║                  Mobile App Testing                         ║
echo  ╚══════════════════════════════════════════════════════════════╝
echo.

cd /d "C:\xampp\htdocs\binest\binest"

echo  [INFO] Project: %CD%
echo  [INFO] API URL: https://tensemock.in/api
echo.

echo  [1/3] Checking Node.js...
if exist "D:\node.exe" (
    echo      ✓ Node.js found
) else (
    echo      ✗ Node.js not found
    echo      Trying system Node...
)

echo.
echo  [2/3] Starting Expo server...
echo      This will show QR code for mobile testing
echo.

npx expo start --tunnel --clear

echo.
echo  ╔══════════════════════════════════════════════════════════════╗
echo  ║                    SERVER STARTED!                          ║
echo  ║                                                              ║
echo  ║  Scan QR code with Expo Go app on your phone               ║
echo  ║  Download Expo Go from Play Store                           ║
echo  ║  Make sure phone and laptop are on same WiFi                ║
echo  ╚══════════════════════════════════════════════════════════════╝
echo.

pause

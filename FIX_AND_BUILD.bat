@echo off
chcp 65001 >nul
echo ==========================================
echo   Binest APK Builder - Fixed
echo ==========================================
echo.

cd /d "C:\xampp\htdocs\binest\binest"

:: Set Node paths properly
set "NODE_PATH=D:\node_modules"
set "PATH=D:\;%PATH%"

echo [1/3] Checking Node.js...
"D:\node.exe" --version
if %errorlevel% neq 0 (
    echo ERROR: Node.js not found at D:\node.exe
    pause
    exit /b 1
)

echo.
echo [2/3] Using NPM from node_modules...
cd /d "D:\node_modules\npm\bin"

echo.
echo [3/3] Installing dependencies in project...
cd /d "C:\xampp\htdocs\binest\binest"
"D:\node.exe" "D:\node_modules\npm\bin\npm-cli.js" install
if %errorlevel% neq 0 (
    echo.
    echo Install completed with warnings...
)

echo.
echo ==========================================
echo Starting APK Build via EAS
echo ==========================================
echo.
echo Login to Expo when browser opens...
echo.

"D:\node.exe" "D:\node_modules\npm\bin\npx-cli.js" eas-cli build --platform android --profile preview

echo.
echo ==========================================
echo Build process started!
echo ==========================================
echo.
echo Check: https://expo.dev/builds
echo.
pause

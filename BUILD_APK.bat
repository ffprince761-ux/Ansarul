@echo off
echo ==========================================
echo   Binest APK Builder
echo ==========================================
echo.

cd /d "C:\xampp\htdocs\binest\binest"

echo [1/4] Checking Node.js...
node --version
if errorlevel 1 (
    echo ERROR: Node.js not found!
    pause
    exit /b 1
)

echo.
echo [2/4] Installing dependencies...
call npm install
if errorlevel 1 (
    echo ERROR: npm install failed!
    pause
    exit /b 1
)

echo.
echo [3/4] Installing EAS CLI...
call npm install -g eas-cli
if errorlevel 1 (
    echo WARNING: EAS CLI install failed, trying npx...
)

echo.
echo [4/4] Starting APK Build...
echo This will open a browser to login to Expo...
echo.
echo Choose: "preview" profile for APK
echo.

:: Try using npx eas-cli
call npx eas-cli build --platform android --profile preview

if errorlevel 1 (
    echo.
    echo ==========================================
    echo Build command failed!
    echo ==========================================
    echo.
    echo Try this manual command:
    echo npx eas-cli build --platform android --profile preview
    echo.
    pause
    exit /b 1
)

echo.
echo ==========================================
echo Build process started!
echo ==========================================
echo.
echo Check your email or visit:
echo https://expo.dev/accounts/[your-account]/projects/binest/builds
echo.
pause

@echo off
echo ==========================================
echo   Binest Local APK Builder
echo   (Requires Android Studio)
echo ==========================================
echo.

cd /d "C:\xampp\htdocs\binest\binest"

echo [1/3] Installing dependencies...
call npm install

echo.
echo [2/3] Prebuilding Android project...
call npx expo prebuild --platform android

echo.
echo [3/3] Building APK...
echo This requires Android Studio and Android SDK!
echo.

cd android

:: Check if gradlew exists
if exist "gradlew.bat" (
    echo Running Gradle build...
    call gradlew.bat assembleRelease
    
    if errorlevel 1 (
        echo.
        echo ERROR: Build failed!
        echo Make sure Android Studio is installed and configured.
        pause
        exit /b 1
    )
    
    echo.
    echo ==========================================
    echo APK Build Complete!
    echo ==========================================
    echo.
    echo APK Location:
    echo C:\xampp\htdocs\binest\binest\android\app\build\outputs\apk\release\app-release.apk
    echo.
    
    :: Copy APK to easy location
    copy "app\build\outputs\apk\release\app-release.apk" "..\..\Binest-APK.apk"
    echo.
    echo Also copied to: C:\xampp\htdocs\binest\binest\Binest-APK.apk
    
) else (
    echo ERROR: gradlew.bat not found!
    echo Please run: npx expo prebuild first
    pause
    exit /b 1
)

echo.
pause

@echo off
setlocal enabledelayedexpansion

:: ============================================================
:: Moodle Plugin Installer - local_activity_utils
:: ============================================================
:: This script automatically installs the activity_utils plugin
:: into your Moodle installation.
:: ============================================================

echo.
echo ============================================================
echo   Moodle Plugin Installer - local_activity_utils
echo ============================================================
echo.

:: Get the directory where this script is located
set "SCRIPT_DIR=%~dp0"

:: Set the Moodle installation path
set "MOODLE_PATH=C:\Users\nthol\Documents\Projects\LMS\moodle\MoodleWindowsInstaller-latest\server\moodle"
set "PLUGIN_NAME=activity_utils"
set "PLUGIN_TYPE=local"
set "DEST_PATH=%MOODLE_PATH%\%PLUGIN_TYPE%\%PLUGIN_NAME%"

:: Check if Moodle directory exists
if not exist "%MOODLE_PATH%" (
    echo [ERROR] Moodle directory not found at:
    echo %MOODLE_PATH%
    echo.
    echo Please edit this script and set the correct MOODLE_PATH
    pause
    exit /b 1
)

echo [INFO] Moodle installation found at: %MOODLE_PATH%
echo [INFO] Plugin will be installed to: %DEST_PATH%
echo.

:: Check if plugin directory already exists
if exist "%DEST_PATH%" (
    echo [WARNING] Plugin directory already exists!
    echo This will overwrite the existing plugin files.
    echo.
    set /p "CONFIRM=Do you want to continue? (Y/N): "
    if /i not "!CONFIRM!"=="Y" (
        echo [INFO] Installation cancelled.
        pause
        exit /b 0
    )
    echo [INFO] Removing existing plugin directory...
    rmdir /s /q "%DEST_PATH%"
)

:: Create plugin directory
echo [INFO] Creating plugin directory...
mkdir "%DEST_PATH%"
if errorlevel 1 (
    echo [ERROR] Failed to create plugin directory
    pause
    exit /b 1
)

:: Create subdirectories
echo [INFO] Creating subdirectories...
mkdir "%DEST_PATH%\classes\external"
mkdir "%DEST_PATH%\db"
mkdir "%DEST_PATH%\lang\en"
mkdir "%DEST_PATH%\tests"

:: Copy plugin files
echo [INFO] Copying plugin files...

copy /y "%SCRIPT_DIR%version.php" "%DEST_PATH%\version.php" >nul
if errorlevel 1 (
    echo [ERROR] Failed to copy version.php
    pause
    exit /b 1
)

copy /y "%SCRIPT_DIR%classes\helper.php" "%DEST_PATH%\classes\helper.php" >nul
if errorlevel 1 (
    echo [ERROR] Failed to copy classes\helper.php
    pause
    exit /b 1
)

copy /y "%SCRIPT_DIR%classes\external\create_assignment.php" "%DEST_PATH%\classes\external\create_assignment.php" >nul
if errorlevel 1 (
    echo [ERROR] Failed to copy create_assignment.php
    pause
    exit /b 1
)

copy /y "%SCRIPT_DIR%classes\external\create_section.php" "%DEST_PATH%\classes\external\create_section.php" >nul
if errorlevel 1 (
    echo [ERROR] Failed to copy create_section.php
    pause
    exit /b 1
)

copy /y "%SCRIPT_DIR%classes\external\create_page.php" "%DEST_PATH%\classes\external\create_page.php" >nul
if errorlevel 1 (
    echo [ERROR] Failed to copy create_page.php
    pause
    exit /b 1
)

copy /y "%SCRIPT_DIR%classes\external\create_file.php" "%DEST_PATH%\classes\external\create_file.php" >nul
if errorlevel 1 (
    echo [ERROR] Failed to copy create_file.php
    pause
    exit /b 1
)

copy /y "%SCRIPT_DIR%classes\external\delete_assignment.php" "%DEST_PATH%\classes\external\delete_assignment.php" >nul
if errorlevel 1 (
    echo [ERROR] Failed to copy delete_assignment.php
    pause
    exit /b 1
)

copy /y "%SCRIPT_DIR%classes\external\create_subsection.php" "%DEST_PATH%\classes\external\create_subsection.php" >nul
if errorlevel 1 (
    echo [ERROR] Failed to copy create_subsection.php
    pause
    exit /b 1
)

copy /y "%SCRIPT_DIR%classes\external\create_book.php" "%DEST_PATH%\classes\external\create_book.php" >nul
if errorlevel 1 (
    echo [ERROR] Failed to copy create_book.php
    pause
    exit /b 1
)

copy /y "%SCRIPT_DIR%classes\external\add_book_chapter.php" "%DEST_PATH%\classes\external\add_book_chapter.php" >nul
if errorlevel 1 (
    echo [ERROR] Failed to copy add_book_chapter.php
    pause
    exit /b 1
)

copy /y "%SCRIPT_DIR%classes\external\get_book.php" "%DEST_PATH%\classes\external\get_book.php" >nul
if errorlevel 1 (
    echo [ERROR] Failed to copy get_book.php
    pause
    exit /b 1
)

copy /y "%SCRIPT_DIR%db\access.php" "%DEST_PATH%\db\access.php" >nul
if errorlevel 1 (
    echo [ERROR] Failed to copy db\access.php
    pause
    exit /b 1
)

copy /y "%SCRIPT_DIR%db\services.php" "%DEST_PATH%\db\services.php" >nul
if errorlevel 1 (
    echo [ERROR] Failed to copy db\services.php
    pause
    exit /b 1
)

copy /y "%SCRIPT_DIR%lang\en\local_activity_utils.php" "%DEST_PATH%\lang\en\local_activity_utils.php" >nul
if errorlevel 1 (
    echo [ERROR] Failed to copy language file
    pause
    exit /b 1
)

copy /y "%SCRIPT_DIR%tests\subsection_and_activities_test.php" "%DEST_PATH%\tests\subsection_and_activities_test.php" >nul
if errorlevel 1 (
    echo [ERROR] Failed to copy test file
    pause
    exit /b 1
)

:: Copy README if exists
if exist "%SCRIPT_DIR%README.md" (
    copy /y "%SCRIPT_DIR%README.md" "%DEST_PATH%\README.md" >nul
)

echo.
echo ============================================================
echo   Installation completed successfully!
echo ============================================================
echo.
echo Files copied to: %DEST_PATH%
echo.
echo NEXT STEPS:
echo 1. Open your web browser
echo 2. Go to your Moodle site admin page:
echo    http://localhost/admin/
echo 3. Moodle will detect the new plugin and prompt you to upgrade
echo 4. Follow the on-screen instructions to complete the installation
echo.
echo NOTE: You do NOT need to restart the Moodle server.
echo       Just visit the admin page to complete the installation.
echo.
echo ============================================================
pause

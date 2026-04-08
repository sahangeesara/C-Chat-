@echo off
REM Start Laravel development server with large file upload support
REM This script sets up the environment for 1GB file uploads

echo.
echo ========================================
echo Laravel ChatApp with 1GB Upload Support
echo ========================================
echo.

REM Check if php.exe exists in PATH
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: PHP is not in your PATH
    echo Please install PHP or add it to your PATH environment variable
    pause
    exit /b 1
)

REM Get PHP version
for /f "tokens=*" %%i in ('php -v') do (
    set phpversion=%%i
    goto :break_php_version
)
:break_php_version

echo Using: %phpversion%
echo.

REM Check if .env file exists
if not exist .env (
    echo ERROR: .env file not found
    echo Please ensure you are in the project root directory
    pause
    exit /b 1
)

echo Starting Laravel development server...
echo Listening on: http://127.0.0.1:8000
echo.
echo Large file upload support:
echo - upload_max_filesize: 1024M
echo - post_max_size: 1024M
echo - max_execution_time: 300s
echo - max_input_time: 300s
echo.
echo Press Ctrl+C to stop the server
echo.

REM Start Laravel server with runtime PHP limits for large uploads
php -d upload_max_filesize=1024M -d post_max_size=1024M -d max_execution_time=300 -d max_input_time=300 artisan serve --host=127.0.0.1 --port=8000

pause

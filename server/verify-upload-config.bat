@echo off
REM Verify 1GB Upload Configuration
REM This script checks if all required changes are in place

echo.
echo ========================================
echo 1GB File Upload Configuration Verification
echo ========================================
echo.

setlocal enabledelayedexpansion
set /a checksPerformed=0
set /a checksPassed=0

REM Function to check if file exists
if exist "config\upload.php" (
    echo [PASS] config\upload.php exists
    set /a checksPassed+=1
) else (
    echo [FAIL] config\upload.php NOT FOUND
)
set /a checksPerformed+=1

if exist "app\Http\Middleware\HandleLargeFileUploads.php" (
    echo [PASS] Middleware exists
    set /a checksPassed+=1
) else (
    echo [FAIL] Middleware NOT FOUND
)
set /a checksPerformed+=1

if exist ".env" (
    echo [PASS] .env file exists
    set /a checksPassed+=1
) else (
    echo [FAIL] .env NOT FOUND
)
set /a checksPerformed+=1

if exist "public\.htaccess" (
    echo [PASS] public\.htaccess exists
    set /a checksPassed+=1
) else (
    echo [FAIL] public\.htaccess NOT FOUND
)
set /a checksPerformed+=1

if exist "php-large-uploads.ini" (
    echo [PASS] php-large-uploads.ini exists
    set /a checksPassed+=1
) else (
    echo [FAIL] php-large-uploads.ini NOT FOUND
)
set /a checksPerformed+=1

if exist "UPLOAD_SETUP.md" (
    echo [PASS] UPLOAD_SETUP.md exists
    set /a checksPassed+=1
) else (
    echo [FAIL] UPLOAD_SETUP.md NOT FOUND
)
set /a checksPerformed+=1

if exist "UPGRADE_SUMMARY.md" (
    echo [PASS] UPGRADE_SUMMARY.md exists
    set /a checksPassed+=1
) else (
    echo [FAIL] UPGRADE_SUMMARY.md NOT FOUND
)
set /a checksPerformed+=1

if exist "start-server-large-uploads.bat" (
    echo [PASS] Batch script exists
    set /a checksPassed+=1
) else (
    echo [FAIL] Batch script NOT FOUND
)
set /a checksPerformed+=1

if exist "start-server-large-uploads.ps1" (
    echo [PASS] PowerShell script exists
    set /a checksPassed+=1
) else (
    echo [FAIL] PowerShell script NOT FOUND
)
set /a checksPerformed+=1

echo.
echo ========================================
echo Verification Results
echo ========================================
echo Checks Passed: %checksPassed% / %checksPerformed%
echo.

if %checksPassed% equ %checksPerformed% (
    echo SUCCESS: All checks passed!
    echo.
    echo Next Steps:
    echo 1. Clear Laravel cache: php artisan config:cache
    echo 2. Run migrations: php artisan migrate
    echo 3. Create storage link: php artisan storage:link
    echo 4. Start server: php artisan serve
) else (
    echo WARNING: Some checks failed.
    echo Please review the errors above.
)

echo.
pause


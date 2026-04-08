#!/usr/bin/env pwsh

# Start Laravel development server with large file upload support
# PowerShell script for Windows

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Laravel ChatApp with 1GB Upload Support" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if php.exe exists
try {
    $phpVersion = php -v 2>&1 | Select-Object -First 1
    Write-Host "Using: $phpVersion" -ForegroundColor Green
} catch {
    Write-Host "ERROR: PHP is not in your PATH" -ForegroundColor Red
    Write-Host "Please install PHP or add it to your PATH environment variable" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host ""

# Check if .env file exists
if (-Not (Test-Path ".env")) {
    Write-Host "ERROR: .env file not found" -ForegroundColor Red
    Write-Host "Please ensure you are in the project root directory" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host "Starting Laravel development server..." -ForegroundColor Green
Write-Host "Listening on: http://127.0.0.1:8000" -ForegroundColor Yellow
Write-Host ""
Write-Host "Large file upload support (applied via PHP CLI flags):" -ForegroundColor Cyan
Write-Host "- upload_max_filesize: 1024M"
Write-Host "- post_max_size: 1024M"
Write-Host "- max_execution_time: 300s"
Write-Host "- max_input_time: 300s"
Write-Host ""
Write-Host "Press Ctrl+C to stop the server" -ForegroundColor Yellow
Write-Host ""

# Start Laravel server with runtime PHP limits for large uploads.
php -d upload_max_filesize=1024M -d post_max_size=1024M -d max_execution_time=300 -d max_input_time=300 artisan serve --host=127.0.0.1 --port=8000

Read-Host "Press Enter to exit"

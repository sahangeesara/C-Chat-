#!/usr/bin/env powershell

# Verify 1GB Upload Configuration
# This script checks if all required changes are in place

Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║        1GB File Upload Configuration Verification          ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

$checksPerformed = 0
$checksPassed = 0

function Test-FileExists {
    param([string]$Path, [string]$Description)
    $checksPerformed++

    if (Test-Path $Path) {
        Write-Host "✅ $Description" -ForegroundColor Green
        $global:checksPassed++
        return $true
    } else {
        Write-Host "❌ $Description - NOT FOUND" -ForegroundColor Red
        return $false
    }
}

function Test-FileContains {
    param([string]$Path, [string]$Pattern, [string]$Description)
    $checksPerformed++

    if (-Not (Test-Path $Path)) {
        Write-Host "❌ $Description - FILE NOT FOUND" -ForegroundColor Red
        return $false
    }

    $content = Get-Content $Path -Raw
    if ($content -match $Pattern) {
        Write-Host "✅ $Description" -ForegroundColor Green
        $global:checksPassed++
        return $true
    } else {
        Write-Host "❌ $Description - PATTERN NOT FOUND" -ForegroundColor Red
        return $false
    }
}

Write-Host "📋 Checking Configuration Files..." -ForegroundColor Yellow
Write-Host ""

# Check config files
Test-FileExists "config/upload.php" "config/upload.php exists"
Test-FileExists "app/Http/Middleware/HandleLargeFileUploads.php" "Middleware exists"
Test-FileExists ".env" ".env file exists"
Test-FileExists "public/.htaccess" "public/.htaccess exists"
Test-FileExists "php-large-uploads.ini" "php-large-uploads.ini exists"

Write-Host ""
Write-Host "📋 Checking .env Configuration..." -ForegroundColor Yellow
Write-Host ""

Test-FileContains ".env" "UPLOAD_MAX_FILE_SIZE_KB=1048576" ".env has UPLOAD_MAX_FILE_SIZE_KB"

Write-Host ""
Write-Host "📋 Checking Laravel Files..." -ForegroundColor Yellow
Write-Host ""

Test-FileContains "app/Http/Controllers/ChatController.php" "1048576" "ChatController has 1GB validation"
Test-FileContains "app/Http/Kernel.php" "large.uploads" "Kernel.php has middleware alias"
Test-FileContains "routes/api.php" "large.uploads" "routes/api.php has middleware applied"

Write-Host ""
Write-Host "📋 Checking Documentation..." -ForegroundColor Yellow
Write-Host ""

Test-FileExists "UPLOAD_SETUP.md" "UPLOAD_SETUP.md documentation exists"
Test-FileExists "UPGRADE_SUMMARY.md" "UPGRADE_SUMMARY.md exists"

Write-Host ""
Write-Host "📋 Checking Helper Scripts..." -ForegroundColor Yellow
Write-Host ""

Test-FileExists "start-server-large-uploads.bat" "Batch script exists"
Test-FileExists "start-server-large-uploads.ps1" "PowerShell script exists"

Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║                    Verification Results                     ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

$percentage = [math]::Round(($checksPassed / $checksPerformed) * 100)

Write-Host "Checks Passed: $checksPassed / $checksPerformed ($percentage%)" -ForegroundColor Yellow
Write-Host ""

if ($checksPassed -eq $checksPerformed) {
    Write-Host "✅ All checks passed! Your 1GB upload configuration is ready." -ForegroundColor Green
    Write-Host ""
    Write-Host "Next Steps:" -ForegroundColor Cyan
    Write-Host "1. Clear Laravel cache: php artisan config:cache" -ForegroundColor White
    Write-Host "2. Run migrations: php artisan migrate" -ForegroundColor White
    Write-Host "3. Create storage link: php artisan storage:link" -ForegroundColor White
    Write-Host "4. Start server: php artisan serve" -ForegroundColor White
} else {
    Write-Host "⚠️  Some checks failed. Please review the errors above." -ForegroundColor Red
    Write-Host ""
    Write-Host "Common Issues:" -ForegroundColor Yellow
    Write-Host "- Missing files: Run 'git pull' to get latest changes" -ForegroundColor White
    Write-Host "- Wrong location: Make sure you're in the server directory" -ForegroundColor White
}

Write-Host ""
Write-Host "For detailed setup instructions, see: UPLOAD_SETUP.md" -ForegroundColor Yellow
Write-Host ""

Read-Host "Press Enter to exit"


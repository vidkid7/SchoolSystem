# School System - One-time setup and run
# Requires: PHP 8.3+ and Composer. Run from project root.

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

# Refresh PATH so newly installed tools (Composer, PHP) are found
$env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")

# Resolve Composer (PATH or common install path)
$composer = $null
if (Get-Command composer -ErrorAction SilentlyContinue) { $composer = "composer" }
elseif (Test-Path "C:\ProgramData\ComposerSetup\bin\composer.bat") { $composer = "C:\ProgramData\ComposerSetup\bin\composer.bat" }
if (-not $composer) {
    Write-Host "ERROR: Composer not found. Add it to PATH or install to C:\ProgramData\ComposerSetup\bin" -ForegroundColor Red
    exit 1
}

# Check PHP
try { php -v | Out-Null } catch {
    Write-Host "ERROR: PHP not found. Add PHP to PATH (e.g. XAMPP, Laragon, or php.net)." -ForegroundColor Red
    exit 1
}

Write-Host "Installing PHP dependencies..." -ForegroundColor Cyan
& $composer install --no-interaction

if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    Write-Host "Created .env from .env.example" -ForegroundColor Green
}
php artisan key:generate --ansi

Write-Host "Running migrations..." -ForegroundColor Cyan
php artisan migrate --force

Write-Host "Seeding database..." -ForegroundColor Cyan
php artisan db:seed --force

if (-not (Test-Path "public\storage")) {
    php artisan storage:link
}

Write-Host "Installing frontend dependencies and building..." -ForegroundColor Cyan
npm install
npm run build

Write-Host "Starting server at http://127.0.0.1:8000" -ForegroundColor Green
php artisan serve

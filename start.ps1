#Requires -Version 5.1
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# ─── Adjust these paths if needed ────────────────────────────────────────────
$NGINX_DIR = "C:\nginx"
$BIO_DIR   = "D:\System Development\Production Environment\biometric-service"
# ─────────────────────────────────────────────────────────────────────────────

Push-Location $PSScriptRoot

if (-not (Test-Path "$NGINX_DIR\nginx.exe")) {
    Write-Host "ERROR: nginx.exe not found at $NGINX_DIR" -ForegroundColor Red
    Pop-Location
    exit 1
}

# ── Tear down any existing instances ─────────────────────────────────────────
Write-Host "Stopping any existing nginx / artisan / biometric processes ..." -ForegroundColor DarkGray

Start-Process -FilePath "$NGINX_DIR\nginx.exe" `
    -ArgumentList "-s", "quit" `
    -WorkingDirectory $NGINX_DIR `
    -Wait -ErrorAction SilentlyContinue
Start-Sleep -Milliseconds 800

Get-Process -Name nginx -ErrorAction SilentlyContinue |
    Stop-Process -Force -ErrorAction SilentlyContinue

# Stop XAMPP Apache if running — it conflicts with nginx on port 443
$apacheService = Get-Service -Name 'Apache2.4' -ErrorAction SilentlyContinue
if ($apacheService -and $apacheService.Status -eq 'Running') {
    Write-Host "Stopping XAMPP Apache2.4 (conflicts with nginx on port 443) ..." -ForegroundColor DarkGray
    Stop-Service -Name 'Apache2.4' -Force -ErrorAction SilentlyContinue
    Start-Sleep -Milliseconds 800
}

# Kill anything holding the ports we need (skip 443 to avoid killing system processes)
foreach ($port in @('8001', '7870')) {
    $pids = (netstat -ano | Select-String ":$port\s") -replace '.*\s(\d+)$','$1' |
            Select-Object -Unique
    foreach ($p in $pids) {
        if ($p -match '^\d+$' -and [int]$p -gt 0) {
            Stop-Process -Id ([int]$p) -Force -ErrorAction SilentlyContinue
        }
    }
}

Start-Sleep -Milliseconds 600
# ─────────────────────────────────────────────────────────────────────────────

# ── Laravel optimizations ─────────────────────────────────────────────────────
Write-Host "Running php artisan optimize:clear ..." -ForegroundColor Cyan
php artisan optimize:clear

Write-Host "Running php artisan filament:optimize ..." -ForegroundColor Cyan
php artisan filament:optimize
# ─────────────────────────────────────────────────────────────────────────────

# Copy nginx.conf and start nginx
Write-Host "Copying nginx.conf -> $NGINX_DIR\conf\nginx.conf ..." -ForegroundColor Cyan
Copy-Item -Path "$PSScriptRoot\nginx.conf" -Destination "$NGINX_DIR\conf\nginx.conf" -Force

Write-Host "Starting nginx  (HTTPS mct.lan:443  ->  HTTP 127.0.0.1:8001) ..." -ForegroundColor Cyan
Start-Process -FilePath "$NGINX_DIR\nginx.exe" -WorkingDirectory $NGINX_DIR
Start-Sleep -Milliseconds 800

# Start Laravel
$env:PHP_CLI_SERVER_WORKERS = 4

Write-Host "Starting php artisan serve on 127.0.0.1:8001 ..." -ForegroundColor Cyan
$artisan = Start-Process -FilePath "php" `
    -ArgumentList "artisan", "serve", "--host=127.0.0.1", "--port=8001" `
    -WorkingDirectory $PSScriptRoot `
    -PassThru

# Start queue worker
Write-Host "Starting php artisan queue:listen ..." -ForegroundColor Cyan
$queue = Start-Process -FilePath "php" `
    -ArgumentList "artisan", "queue:listen", "--tries=1" `
    -WorkingDirectory $PSScriptRoot `
    -PassThru

# ── Face Biometrics Service ───────────────────────────────────────────────────
$bio = $null
$bioVenv = "$BIO_DIR\venv\Scripts\python.exe"
if (-not (Test-Path $bioVenv)) {
    Write-Host "WARNING: biometric-service venv not found." -ForegroundColor Yellow
    Write-Host "         Run .\install.ps1 inside $BIO_DIR first." -ForegroundColor Yellow
} else {
    Write-Host "Starting Face Biometrics service on 127.0.0.1:7870 ..." -ForegroundColor Cyan
    $bio = Start-Process -FilePath $bioVenv `
        -ArgumentList "-m", "uvicorn", "service:app", `
                      "--host", "127.0.0.1", "--port", "7870", `
                      "--workers", "4", "--limit-concurrency", "4", `
                      "--timeout-keep-alive", "10", "--backlog", "256", `
                      "--log-level", "info" `
        -WorkingDirectory $BIO_DIR `
        -PassThru
    Start-Sleep -Milliseconds 1500
}
# ─────────────────────────────────────────────────────────────────────────────

# Build frontend assets
Write-Host "Running npm run build ..." -ForegroundColor Cyan
npm run build
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: npm run build failed." -ForegroundColor Red
    Pop-Location
    exit 1
}

$bioPid = if ($bio) { $bio.Id } else { "not started" }

Write-Host ""
Write-Host "=============================================" -ForegroundColor Green
Write-Host "  App running at https://mct.lan            " -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Green
Write-Host ""
Write-Host "Artisan PID : $($artisan.Id)   Queue PID: $($queue.Id)   Bio PID: $bioPid" -ForegroundColor DarkGray
Write-Host "Press Ctrl+C to stop all services." -ForegroundColor Yellow
Write-Host ""

try {
    while ($true) { Start-Sleep -Seconds 2 }
}
finally {
    Write-Host ""
    Write-Host "Stopping services ..." -ForegroundColor Yellow

    Start-Process -FilePath "$NGINX_DIR\nginx.exe" `
        -ArgumentList "-s", "quit" `
        -WorkingDirectory $NGINX_DIR -Wait -ErrorAction SilentlyContinue

    foreach ($proc in @($artisan, $queue, $bio)) {
        if ($null -ne $proc -and -not $proc.HasExited) {
            Get-CimInstance Win32_Process |
                Where-Object { $_.ParentProcessId -eq $proc.Id } |
                ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }
            Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue
        }
    }

    Pop-Location
    Write-Host "All services stopped." -ForegroundColor Green
}

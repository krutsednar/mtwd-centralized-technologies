#Requires -Version 5.1
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# ─── Adjust these paths ───────────────────────────────────────────────────────
$NGINX_DIR = "C:\nginx"
$BIO_DIR   = "D:\System Development\Production Environment\biometric-service"
# ─────────────────────────────────────────────────────────────────────────────

Push-Location $PSScriptRoot

if (-not (Test-Path "$NGINX_DIR\nginx.exe")) {
    Write-Host "ERROR: nginx.exe not found at $NGINX_DIR" -ForegroundColor Red
    Write-Host "Edit the `$NGINX_DIR variable at the top of this script." -ForegroundColor Yellow
    Pop-Location
    exit 1
}

# ── Tear down any existing instances first (prevents port stacking) ──────────
Write-Host "Stopping any existing nginx / artisan / biometric processes ..." -ForegroundColor DarkGray

# Graceful nginx quit; ignore errors if none running
Start-Process -FilePath "$NGINX_DIR\nginx.exe" `
    -ArgumentList "-s", "quit" `
    -WorkingDirectory $NGINX_DIR `
    -Wait -ErrorAction SilentlyContinue
Start-Sleep -Milliseconds 800

# Force-kill any remaining nginx.exe (stale or elevated leftovers)
Get-Process -Name nginx -ErrorAction SilentlyContinue |
    Stop-Process -Force -ErrorAction SilentlyContinue

# Kill anything holding the ports we need
$ports = @('8001', '443', '7870')
foreach ($port in $ports) {
    $pids = (netstat -ano | Select-String ":$port\s") -replace '.*\s(\d+)$','$1' |
            Select-Object -Unique
    foreach ($p in $pids) {
        if ($p -match '^\d+$' -and [int]$p -gt 0) {
            Stop-Process -Id ([int]$p) -Force -ErrorAction SilentlyContinue
        }
    }
}

Start-Sleep -Milliseconds 600   # let OS release the ports
# ─────────────────────────────────────────────────────────────────────────────

# Copy project nginx.conf into nginx's conf/ directory, then start nginx
Write-Host "Copying nginx.conf -> $NGINX_DIR\conf\nginx.conf ..." -ForegroundColor Cyan
Copy-Item -Path "$PSScriptRoot\nginx.conf" -Destination "$NGINX_DIR\conf\nginx.conf" -Force

$env:PHP_CLI_SERVER_WORKERS = 4

Write-Host "Starting nginx  (HTTPS 192.168.0.58:443  ->  HTTP 127.0.0.1:8001) ..." -ForegroundColor Cyan
Start-Process -FilePath "$NGINX_DIR\nginx.exe" -WorkingDirectory $NGINX_DIR
Start-Sleep -Milliseconds 800   # give nginx a moment to bind the port

Write-Host "Starting php artisan serve on 127.0.0.1:8001 ..." -ForegroundColor Cyan
$artisan = Start-Process -FilePath "php" `
    -ArgumentList "artisan", "serve", "--host=127.0.0.1", "--port=8001" `
    -WorkingDirectory $PSScriptRoot `
    -PassThru

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
                      "--workers", "1", "--log-level", "info" `
        -WorkingDirectory $BIO_DIR `
        -PassThru
    Start-Sleep -Milliseconds 1500  # give models a moment to begin loading
}
# ─────────────────────────────────────────────────────────────────────────────

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
Write-Host "  App running at https://192.168.0.58       " -ForegroundColor Green
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

    # Graceful nginx shutdown
    Start-Process -FilePath "$NGINX_DIR\nginx.exe" `
        -ArgumentList "-s", "quit" `
        -WorkingDirectory $NGINX_DIR -Wait -ErrorAction SilentlyContinue

    # Kill artisan, queue, and biometric service (plus their child processes)
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

#Requires -Version 5.1
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# ─── Adjust this to your nginx installation directory ───────────────────────
$NGINX_DIR = "C:\nginx"
# ────────────────────────────────────────────────────────────────────────────

Push-Location $PSScriptRoot

if (-not (Test-Path "$NGINX_DIR\nginx.exe")) {
    Write-Host "ERROR: nginx.exe not found at $NGINX_DIR" -ForegroundColor Red
    Write-Host "Edit the `$NGINX_DIR variable at the top of this script." -ForegroundColor Yellow
    Pop-Location
    exit 1
}

# ── Tear down any existing instances first (prevents port stacking) ──────────
Write-Host "Stopping any existing nginx / artisan processes ..." -ForegroundColor DarkGray

# Graceful nginx quit; ignore errors if none running
Start-Process -FilePath "$NGINX_DIR\nginx.exe" `
    -ArgumentList "-s", "quit" `
    -WorkingDirectory $NGINX_DIR `
    -Wait -ErrorAction SilentlyContinue
Start-Sleep -Milliseconds 800

# Force-kill any remaining nginx.exe (stale or elevated leftovers)
Get-Process -Name nginx -ErrorAction SilentlyContinue |
    Stop-Process -Force -ErrorAction SilentlyContinue

# Kill any php.exe holding port 8001 or port 1978
$ports = @('8001', '1978')
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

Write-Host "Starting nginx  (HTTPS 192.168.0.58:1978  ->  HTTP 127.0.0.1:8001) ..." -ForegroundColor Cyan
Start-Process -FilePath "$NGINX_DIR\nginx.exe" -WorkingDirectory $NGINX_DIR
Start-Sleep -Milliseconds 800   # give nginx a moment to bind the port

Write-Host "Starting php artisan serve on 127.0.0.1:8000 ..." -ForegroundColor Cyan
$artisan = Start-Process -FilePath "php" `
    -ArgumentList "artisan", "serve", "--host=127.0.0.1", "--port=8001" `
    -WorkingDirectory $PSScriptRoot `
    -PassThru

Write-Host "Starting php artisan queue:listen ..." -ForegroundColor Cyan
$queue = Start-Process -FilePath "php" `
    -ArgumentList "artisan", "queue:listen", "--tries=1" `
    -WorkingDirectory $PSScriptRoot `
    -PassThru

Write-Host "Starting npm run dev (Vite) ..." -ForegroundColor Cyan
$vite = Start-Process -FilePath "cmd.exe" `
    -ArgumentList "/k", "npm run dev" `
    -WorkingDirectory $PSScriptRoot `
    -PassThru

Write-Host ""
Write-Host "=============================================" -ForegroundColor Green
Write-Host "  App running at https://192.168.0.58:1978  " -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Green
Write-Host ""
Write-Host "Artisan PID : $($artisan.Id)   Queue PID: $($queue.Id)   Vite PID: $($vite.Id)" -ForegroundColor DarkGray
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

    # Kill artisan, queue, vite and their child processes (node spawned by npm)
    foreach ($proc in @($artisan, $queue, $vite)) {
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

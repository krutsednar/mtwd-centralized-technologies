#Requires -Version 5.1
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

Push-Location $PSScriptRoot

# ── Kill any php.exe already holding port 8001 ───────────────────────────────
Write-Host "Checking for stale processes on port 8001 ..." -ForegroundColor DarkGray

$pids = (netstat -ano | Select-String ":8001\s") -replace '.*\s(\d+)$', '$1' |
        Select-Object -Unique
foreach ($p in $pids) {
    if ($p -match '^\d+$' -and [int]$p -gt 0) {
        Stop-Process -Id ([int]$p) -Force -ErrorAction SilentlyContinue
        Write-Host "  Killed stale process PID $p" -ForegroundColor DarkGray
    }
}

Start-Sleep -Milliseconds 400   # let the OS release the port

# ── Start Laravel ─────────────────────────────────────────────────────────────
$env:PHP_CLI_SERVER_WORKERS = 4

Write-Host "Starting php artisan serve on 127.0.0.1:8001 ..." -ForegroundColor Cyan
$artisan = Start-Process -FilePath "php" `
    -ArgumentList "artisan", "serve", "--host=127.0.0.1", "--port=8001" `
    -WorkingDirectory $PSScriptRoot `
    -PassThru

# ── Start queue worker ────────────────────────────────────────────────────────
Write-Host "Starting php artisan queue:listen ..." -ForegroundColor Cyan
$queue = Start-Process -FilePath "php" `
    -ArgumentList "artisan", "queue:listen", "--tries=1" `
    -WorkingDirectory $PSScriptRoot `
    -PassThru

# ── Ready ─────────────────────────────────────────────────────────────────────
Write-Host ""
Write-Host "======================================" -ForegroundColor Green
Write-Host "  App running at https://mct.lan      " -ForegroundColor Green
Write-Host "======================================" -ForegroundColor Green
Write-Host ""
Write-Host "  Artisan PID : $($artisan.Id)" -ForegroundColor DarkGray
Write-Host "  Queue PID   : $($queue.Id)" -ForegroundColor DarkGray
Write-Host ""
Write-Host "  XAMPP Apache handles HTTPS (port 443) as a Windows service." -ForegroundColor DarkGray
Write-Host "  Press Ctrl+C to stop artisan and the queue worker." -ForegroundColor Yellow
Write-Host ""

try {
    while ($true) { Start-Sleep -Seconds 2 }
}
finally {
    Write-Host ""
    Write-Host "Stopping services ..." -ForegroundColor Yellow

    foreach ($proc in @($artisan, $queue)) {
        if ($null -ne $proc -and -not $proc.HasExited) {
            Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue
        }
    }

    Pop-Location
    Write-Host "All services stopped." -ForegroundColor Green
}

$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$runtimeDir = Join-Path $projectRoot 'storage\runtime'
$statePath = Join-Path $runtimeDir 'app-server.json'
$stdoutPath = Join-Path $runtimeDir 'php-server.out.log'
$stderrPath = Join-Path $runtimeDir 'php-server.err.log'
$hostName = '127.0.0.1'
$port = 8000
$entryPath = '/adopt'
$url = "http://$hostName`:$port$entryPath"

if (!(Test-Path $runtimeDir)) {
    New-Item -ItemType Directory -Path $runtimeDir -Force | Out-Null
}

function Get-PhpExecutable {
    $command = Get-Command php -ErrorAction SilentlyContinue
    if ($null -eq $command) {
        throw 'PHP executable was not found on PATH.'
    }

    return $command.Source
}

function Get-AppServerProcesses {
    return @(Get-CimInstance Win32_Process | Where-Object {
            $_.Name -eq 'php.exe' -and
            $_.CommandLine -like "*-S $hostName`:$port*" -and
            $_.CommandLine -like '*-t public*'
        })
}

function Get-RunningState {
    $processes = Get-AppServerProcesses
    if ($processes.Count -gt 0) {
        return @{
            pids = @($processes | ForEach-Object { $_.ProcessId })
            url = $url
            entry_path = $entryPath
        }
    }

    if (Test-Path $statePath) {
        Remove-Item $statePath -Force -ErrorAction SilentlyContinue
    }

    return $null
}

$runningState = Get-RunningState
if ($null -ne $runningState) {
    Start-Process $url | Out-Null
    Write-Output "Application already running at $url"
    exit 0
}

$phpPath = Get-PhpExecutable
$arguments = @('-S', "$hostName`:$port", '-t', 'public')
$process = Start-Process -FilePath $phpPath `
    -ArgumentList $arguments `
    -WorkingDirectory $projectRoot `
    -RedirectStandardOutput $stdoutPath `
    -RedirectStandardError $stderrPath `
    -WindowStyle Hidden `
    -PassThru

$ready = $false
for ($attempt = 0; $attempt -lt 20; $attempt++) {
    Start-Sleep -Milliseconds 500

    try {
        $response = Invoke-WebRequest -UseBasicParsing -Uri $url -TimeoutSec 2
        if ($response.StatusCode -ge 200 -and $response.StatusCode -lt 500) {
            $ready = $true
            break
        }
    } catch {
        if ($process.HasExited) {
            break
        }
    }
}

if (!$ready) {
    if (!$process.HasExited) {
        Stop-Process -Id $process.Id -Force -ErrorAction SilentlyContinue
    }

    throw "Application server failed to start at $url. Check $stderrPath"
}

@{
    pid = $process.Id
    pids = @(Get-AppServerProcesses | ForEach-Object { $_.ProcessId })
    url = $url
    entry_path = $entryPath
    host = $hostName
    port = $port
    stdout = $stdoutPath
    stderr = $stderrPath
    started_at = (Get-Date).ToString('s')
} | ConvertTo-Json | Set-Content -Path $statePath -Encoding UTF8

Start-Process $url | Out-Null
Write-Output "Application started at $url"

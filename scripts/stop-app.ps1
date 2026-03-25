$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$runtimeDir = Join-Path $projectRoot 'storage\runtime'
$statePath = Join-Path $runtimeDir 'app-server.json'
$hostName = '127.0.0.1'
$port = 8000

function Get-AppServerProcesses {
    return @(Get-CimInstance Win32_Process | Where-Object {
            $_.Name -eq 'php.exe' -and
            $_.CommandLine -like "*-S $hostName`:$port*" -and
            $_.CommandLine -like '*-t public*'
        })
}

if (!(Test-Path $statePath) -and (Get-AppServerProcesses).Count -eq 0) {
    Write-Output 'Application server is not running.'
    exit 0
}

try {
    $state = Get-Content $statePath -Raw | ConvertFrom-Json
} catch {
    $state = $null
}

$processIds = @()
if ($null -ne $state) {
    if ($null -ne $state.pids) {
        $processIds += @($state.pids)
    } elseif ($null -ne $state.pid) {
        $processIds += @($state.pid)
    }
}

$matchingProcesses = Get-AppServerProcesses
if ($matchingProcesses.Count -gt 0) {
    $processIds += @($matchingProcesses | ForEach-Object { $_.ProcessId })
}

$processIds = @($processIds | Where-Object { $_ -ne $null -and [int] $_ -gt 0 } | Select-Object -Unique)

if ($processIds.Count -eq 0) {
    Remove-Item $statePath -Force -ErrorAction SilentlyContinue
    Write-Output 'Application server process was already stopped.'
    exit 0
}

Get-Process -Id $processIds -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
Remove-Item $statePath -Force -ErrorAction SilentlyContinue

Write-Output ('Application server stopped (PIDs ' + ($processIds -join ', ') + ').')

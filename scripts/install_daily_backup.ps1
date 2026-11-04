param([string]$PhpPath = 'C:\xampp\php\php.exe')
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$runner = Join-Path $PSScriptRoot 'daily_backup.php'
if (!(Test-Path -LiteralPath $PhpPath) -or !(Test-Path -LiteralPath $runner)) { throw 'PHP or the daily backup runner was not found.' }
$action = New-ScheduledTaskAction -Execute $PhpPath -Argument ('"' + $runner + '"') -WorkingDirectory $projectRoot
$trigger = New-ScheduledTaskTrigger -Daily -At '03:00'
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -MultipleInstances IgnoreNew -RestartCount 3 -RestartInterval (New-TimeSpan -Minutes 30) -ExecutionTimeLimit (New-TimeSpan -Minutes 15) -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries
$principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount
Register-ScheduledTask -TaskName 'SharonStore Daily Database Backup' -Action $action -Trigger $trigger -Settings $settings -Principal $principal -Description 'Back up the Sharon Store database every day at 03:00, retry failures, and catch up after downtime.' -Force | Out-Null
$backupDirectory = Join-Path $projectRoot 'backups'
New-Item -ItemType Directory -Path $backupDirectory -Force | Out-Null
@{ task = 'SharonStore Daily Database Backup'; installed_at = [DateTimeOffset]::Now.ToString('o'); timezone = (Get-TimeZone).Id; time = '03:00' } | ConvertTo-Json | Set-Content -LiteralPath (Join-Path $backupDirectory '.daily-schedule.json') -Encoding UTF8
Get-ScheduledTaskInfo -TaskName 'SharonStore Daily Database Backup' | Select-Object NextRunTime,LastRunTime,LastTaskResult

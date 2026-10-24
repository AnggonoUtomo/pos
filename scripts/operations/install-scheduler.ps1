param([switch]$Install)

$ErrorActionPreference = 'Stop'
$root = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '../..')).Path
$runner = Join-Path $PSScriptRoot 'run-scheduler.ps1'
$php = (Get-Command php.exe -ErrorAction Stop).Source
$mysql = Split-Path -Parent (Get-Command mysqldump.exe -ErrorAction Stop).Source
$powershell = Join-Path $env:SystemRoot 'System32/WindowsPowerShell/v1.0/powershell.exe'
$taskName = 'POS-OperationalScheduler'
$arguments = '-NoProfile -NonInteractive -WindowStyle Hidden -ExecutionPolicy RemoteSigned -File "' + $runner + '" -PhpBinary "' + $php + '" -MySqlDirectory "' + $mysql + '"'
$action = New-ScheduledTaskAction -Execute $powershell -Argument $arguments -WorkingDirectory $root
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) -RepetitionInterval (New-TimeSpan -Minutes 1)
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -MultipleInstances IgnoreNew -ExecutionTimeLimit (New-TimeSpan -Minutes 15) -RestartCount 3 -RestartInterval (New-TimeSpan -Minutes 1) -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries
$principal = New-ScheduledTaskPrincipal -UserId ([Security.Principal.WindowsIdentity]::GetCurrent().Name) -LogonType Interactive -RunLevel Limited
$existing = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
if ($existing -and ($existing.Actions.Count -ne 1 -or $existing.Actions[0].Execute -ne $powershell -or $existing.Actions[0].Arguments -ne $arguments -or $existing.Actions[0].WorkingDirectory -ne $root)) {
    throw 'Task dengan nama sama memiliki konfigurasi lain. Tidak ditimpa.'
}
if (-not $Install) {
    Write-Output ('DRY RUN: ' + $taskName + ', tiap menit, akun interaktif terbatas, root ' + $root)
    Write-Output 'Belum memasang task. Gunakan -Install setelah migration siap. Backup hanya berjalan jika diaktifkan di Pengaturan operasional.'
    exit 0
}
if ($existing) {
    Write-Output 'Task POS sudah ada dengan konfigurasi sama; tidak diubah.'
} else {
    Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Principal $principal -Description 'Heartbeat POS; jadwal backup opsional di Pengaturan operasional. Memerlukan akun Windows login dan MySQL aktif.' | Out-Null
    Write-Output ('Task terpasang: ' + $taskName)
}

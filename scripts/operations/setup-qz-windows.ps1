[CmdletBinding()]
param([switch]$Install)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Assert-QzPlatform {
    param([string]$Platform, [string]$Architecture)
    if ($Platform -ne 'Win32NT' -or $Architecture -ne 'X64') {
        throw 'Setup ini hanya untuk Windows x64. Gunakan installer resmi sesuai OS/arsitektur.'
    }
}

function Assert-QzInstaller {
    param([string]$Path, [string]$ExpectedHash)
    if ((Get-FileHash -LiteralPath $Path -Algorithm SHA256).Hash -ne $ExpectedHash) {
        throw 'Hash installer QZ tidak cocok. Installer tidak dijalankan.'
    }
    $signature = Get-AuthenticodeSignature -LiteralPath $Path
    if ($signature.Status -ne 'Valid' -or $null -eq $signature.SignerCertificate -or
        $signature.SignerCertificate.Subject -notmatch 'QZ Industries') {
        throw 'Tanda tangan/publisher installer QZ tidak valid. Installer tidak dijalankan.'
    }
}

function Get-QzInstallation {
    $paths = @(
        (Join-Path $env:ProgramFiles 'QZ Tray\qz-tray.exe'),
        (Join-Path ${env:ProgramFiles(x86)} 'QZ Tray\qz-tray.exe')
    )
    foreach ($key in @('HKLM:\Software\Microsoft\Windows\CurrentVersion\Uninstall',
        'HKLM:\Software\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall',
        'HKCU:\Software\Microsoft\Windows\CurrentVersion\Uninstall')) {
        foreach ($entry in @(Get-ChildItem -LiteralPath $key -ErrorAction SilentlyContinue | Where-Object { $_.PSChildName -match '^QZ' })) {
            $details = Get-ItemProperty -LiteralPath $entry.PSPath
            $location = $details.PSObject.Properties['InstallLocation']
            if ($null -ne $location -and -not [string]::IsNullOrWhiteSpace([string]$location.Value)) {
                $paths += Join-Path ([string]$location.Value) 'qz-tray.exe'
            }
        }
    }
    foreach ($path in ($paths | Select-Object -Unique)) {
        if (Test-Path -LiteralPath $path -PathType Leaf) {
            $file = Get-Item -LiteralPath $path
            $version = $file.VersionInfo.ProductVersion
            if ([string]::IsNullOrWhiteSpace($version)) { $version = 'versi periksa melalui About QZ Tray' }
            return [pscustomobject]@{ Path = $file.FullName; Version = $version }
        }
    }
}

function Invoke-QzSetup {
    param([switch]$Install)
    Assert-QzPlatform -Platform ([Environment]::OSVersion.Platform.ToString()) `
        -Architecture ([Runtime.InteropServices.RuntimeInformation]::OSArchitecture.ToString())
    $existing = Get-QzInstallation
    if ($null -ne $existing) {
        Write-Output ('QZ sudah terpasang: ' + $existing.Version + '. Tidak melakukan install/upgrade ulang.')
        return
    }
    if (@(Get-Process -Name 'qz*' -ErrorAction SilentlyContinue).Count -gt 0) {
        throw 'Proses QZ/setup lain sedang berjalan. Selesaikan pemasangan tersebut dahulu.'
    }
    if (-not $Install) {
        Write-Output 'QZ belum ditemukan. Pemeriksaan saja; tidak download atau mengubah OS. Admin dapat menjalankan ulang dengan -Install.'
        return
    }
    $principal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
    if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
        throw 'Pemasangan memerlukan PowerShell administrator dan persetujuan pemilik PC. Tidak melakukan download/install.'
    }
    $root = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..\..')).Path
    if (-not (Test-Path -LiteralPath (Join-Path $root 'artisan') -PathType Leaf)) {
        throw 'Jalankan setup dari checkout POS yang utuh.'
    }
    $mutex = New-Object Threading.Mutex($false, 'Local\POS-QZ-Setup')
    $locked = $false
    try {
        try { $locked = $mutex.WaitOne(0) } catch [Threading.AbandonedMutexException] { $locked = $true }
        if (-not $locked) { throw 'Setup POS QZ lain sedang berjalan.' }
        if ($null -ne (Get-QzInstallation) -or @(Get-Process -Name 'qz*' -ErrorAction SilentlyContinue).Count -gt 0) {
            throw 'QZ/setup ditemukan setelah pemeriksaan awal. Tidak melanjutkan instalasi kedua.'
        }
        $nonce = [Guid]::NewGuid().ToString('N')
        $directory = Join-Path $root ('storage\app\private\qz-setup-' + $nonce)
        $null = New-Item -ItemType Directory -Path $directory
        $metadata = [ordered]@{
            release = '2.3.0+1'; installed_before = $false
            started_at_utc = [DateTime]::UtcNow.ToString('o')
            startup_entry_before = [bool](Get-ItemProperty -LiteralPath 'HKCU:\Software\Microsoft\Windows\CurrentVersion\Run' -Name 'QZ Tray' -ErrorAction SilentlyContinue)
            qz_root_thumbprints_before = @(Get-ChildItem Cert:\CurrentUser\Root, Cert:\LocalMachine\Root | Where-Object { $_.Subject -match 'QZ Industries|QZ Tray' } | Select-Object -ExpandProperty Thumbprint)
        }
        $metadata | ConvertTo-Json -Depth 3 | Set-Content -LiteralPath (Join-Path $directory 'before.json') -Encoding UTF8
        $installer = Join-Path $directory 'qz-tray-2.3.0+1-x86_64.exe'
        Invoke-WebRequest -UseBasicParsing `
            -Uri 'https://github.com/qzind/tray/releases/download/v2.3.0/qz-tray-2.3.0%2B1-x86_64.exe' `
            -OutFile $installer
        Assert-QzInstaller -Path $installer -ExpectedHash '01a8ae33da31c78f386ef8335024723f2dd2cb7d8bc44ce33079bf6bc0360011'
        $process = Start-Process -FilePath $installer -ArgumentList '/S' -WindowStyle Hidden -Wait -PassThru
        if ($process.ExitCode -ne 0 -or $null -eq (Get-QzInstallation)) {
            throw 'Installer QZ belum terverifikasi berhasil. Periksa instalasi sebelum mengulang; jangan hapus state/certificate.'
        }
        Write-Output 'QZ terpasang. Buka aplikasi QZ Tray lalu Perangkat POS. Driver, izin koneksi dan UAT fisik tetap diperiksa terpisah.'
    } finally {
        if ($locked) { $mutex.ReleaseMutex() }
        $mutex.Dispose()
    }
}

if ($MyInvocation.InvocationName -ne '.') { Invoke-QzSetup -Install:$Install }

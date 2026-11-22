[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)][ValidatePattern('^[a-f0-9]{40}$')][string]$SourceCommit,
    [switch]$FetchBuildTools,
    [switch]$ApplicationPayload,
    [switch]$Distribution
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
$repo = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..\..')).Path
$lock = Get-Content -LiteralPath (Join-Path $PSScriptRoot 'components.lock.json') -Raw | ConvertFrom-Json
if ($Distribution) {
    throw ('Distribusi penuh belum tersedia: ' + $lock.distributionStatus + '. Tidak membuat installer yang seolah siap produksi.')
}
$private = Join-Path $repo 'storage\app\private\windows-installer'
if ($lock.schemaVersion -ne 1) { throw 'Schema komponen tidak didukung.' }
$sdk = $lock.buildTools.dotnet
$sdkRoot = Join-Path $private ('tools\dotnet-' + $sdk.version)
$dotnet = Join-Path $sdkRoot 'dotnet.exe'

function Invoke-Checked {
    param([string]$Executable, [string[]]$Arguments)
    & $Executable @Arguments
    if ($LASTEXITCODE -ne 0) { throw ('Command gagal: ' + [IO.Path]::GetFileName($Executable)) }
}

function Assert-PlainPath {
    param([string]$Path)
    $current = [IO.Path]::GetFullPath($Path)
    while ($current) {
        if ((Test-Path -LiteralPath $current) -and
            ((Get-Item -LiteralPath $current -Force).Attributes -band [IO.FileAttributes]::ReparsePoint)) {
            throw 'Junction/symlink pada path build ditolak.'
        }
        $current = [IO.Path]::GetDirectoryName($current)
    }
}

function Expand-SafeZip {
    param([string]$Archive, [string]$Destination)
    Assert-PlainPath $Destination
    if (Test-Path -LiteralPath $Destination) { throw 'Tujuan ekstraksi harus baru.' }
    $zip = [IO.Compression.ZipFile]::OpenRead($Archive)
    try {
        $names = New-Object 'Collections.Generic.HashSet[string]' ([StringComparer]::OrdinalIgnoreCase)
        foreach ($entry in $zip.Entries) {
            $name = $entry.FullName.TrimEnd('/')
            if (-not $name -or $name.Contains('\') -or $name.Contains(':') -or $name.StartsWith('/') -or
                $name -match '[\x00-\x1f<>"|?*]' -or
                @($name.Split('/') | Where-Object { $_ -in @('', '.', '..') -or $_ -match '[. ]$' -or $_ -match '^(CON|PRN|AUX|NUL|COM[0-9]|LPT[0-9])($|\.)' }).Count -gt 0 -or
                -not $names.Add($name) -or (($entry.ExternalAttributes -shr 16) -band 61440) -eq 40960) {
                throw 'Archive berisi traversal, symlink, duplicate atau nama tidak aman.'
            }
        }
    } finally { $zip.Dispose() }
    [IO.Compression.ZipFile]::ExtractToDirectory($Archive, $Destination)
}

Add-Type -AssemblyName System.IO.Compression.FileSystem
Assert-PlainPath $private
$oldLocation = Get-Location
$savedEnvironment = @{}
$environmentNames = @(Get-ChildItem Env: | Where-Object { $_.Name -match '^(APP_|DB_|VITE_|LARAVEL_|MYSQL_|CACHE_|SESSION_|QUEUE_|MAIL_|LOG_|DOTNET_|NUGET_|COMPOSER_)' } | Select-Object -ExpandProperty Name)
$environmentNames += @('DOTNET_ROOT', 'DOTNET_CLI_HOME', 'DOTNET_CLI_TELEMETRY_OPTOUT', 'DOTNET_SKIP_FIRST_TIME_EXPERIENCE',
    'DOTNET_CLI_WORKLOAD_UPDATE_NOTIFY_DISABLE', 'DOTNET_GENERATE_ASPNET_CERTIFICATE', 'NUGET_PACKAGES', 'COMPOSER_HOME', 'COMPOSER_CACHE_DIR')
foreach ($name in ($environmentNames | Select-Object -Unique)) {
    $savedEnvironment[$name] = [Environment]::GetEnvironmentVariable($name, 'Process')
    [Environment]::SetEnvironmentVariable($name, $null, 'Process')
}
try {
    $null = New-Item -ItemType Directory -Path $private -Force
    $env:DOTNET_ROOT = $sdkRoot
    $env:DOTNET_CLI_HOME = Join-Path $private 'cache\dotnet-home'
    $env:DOTNET_CLI_TELEMETRY_OPTOUT = '1'
    $env:DOTNET_SKIP_FIRST_TIME_EXPERIENCE = '1'
    $env:DOTNET_CLI_WORKLOAD_UPDATE_NOTIFY_DISABLE = '1'
    $env:DOTNET_GENERATE_ASPNET_CERTIFICATE = 'false'
    $env:NUGET_PACKAGES = Join-Path $private 'cache\nuget'
    $env:COMPOSER_HOME = Join-Path $private 'cache\composer-home'
    $env:COMPOSER_CACHE_DIR = Join-Path $private 'cache\composer'
    if (-not (Test-Path -LiteralPath $dotnet -PathType Leaf)) {
        if (-not $FetchBuildTools) { throw 'SDK portable belum tersedia. Gunakan -FetchBuildTools untuk download ZIP build-only yang dikunci.' }
        $downloads = Join-Path $private 'downloads'
        $null = New-Item -ItemType Directory -Path $downloads -Force
        $archive = Join-Path $downloads ('dotnet-sdk-' + $sdk.version + '-win-x64.zip')
        if (-not (Test-Path -LiteralPath $archive -PathType Leaf)) {
            Invoke-WebRequest -UseBasicParsing -Uri $sdk.url -OutFile $archive -TimeoutSec 300
        }
        if ((Get-FileHash -LiteralPath $archive -Algorithm SHA512).Hash -ne $sdk.sha512) {
            throw 'SHA512 SDK berbeda dari metadata Microsoft. Hapus hanya download rusak setelah diperiksa; tidak dieksekusi.'
        }
        $null = New-Item -ItemType Directory -Path (Split-Path -Parent $sdkRoot) -Force
        Expand-SafeZip $archive $sdkRoot
    }
    Assert-PlainPath $dotnet
    $signature = Get-AuthenticodeSignature -LiteralPath $dotnet
    if ($signature.Status -ne 'Valid' -or $null -eq $signature.SignerCertificate -or $signature.SignerCertificate.Subject -notmatch 'O=Microsoft Corporation,') {
        throw 'Authenticode SDK/publisher Microsoft tidak valid.'
    }
    Set-Location -LiteralPath $PSScriptRoot
    $version = & $dotnet --version
    if ($LASTEXITCODE -ne 0 -or $version -ne $sdk.version) { throw 'Versi SDK tidak cocok global.json/manifest.' }
    $commit = & git -C $repo rev-parse --verify ($SourceCommit + '^{commit}')
    if ($LASTEXITCODE -ne 0 -or $commit -ne $SourceCommit) { throw 'Source commit tidak tersedia.' }
    $job = Join-Path $private ('builds\' + $SourceCommit.Substring(0, 12) + '-' + [Guid]::NewGuid().ToString('N'))
    $null = New-Item -ItemType Directory -Path $job
    $toolsOutput = Join-Path $job 'preflight'
    Invoke-Checked $dotnet @('restore', 'tests/Pos.Deployment.Tests/Pos.Deployment.Tests.csproj', '--locked-mode')
    Invoke-Checked $dotnet @('run', '--project', 'tests/Pos.Deployment.Tests/Pos.Deployment.Tests.csproj', '--no-restore', '-c', 'Release')
    Invoke-Checked $dotnet @('restore', 'src/Pos.Setup/Pos.Setup.csproj', '--locked-mode')
    Invoke-Checked $dotnet @('publish', 'src/Pos.Setup/Pos.Setup.csproj', '--no-restore', '-c', 'Release', '-o', $toolsOutput, '--disable-build-servers')
    $verifier = Join-Path $toolsOutput 'Pos.Setup.exe'
    $report = [ordered]@{
        kind = 'internal-preflight-only'; sourceCommit = $SourceCommit; buildToolsCommit = (& git -C $repo rev-parse HEAD)
        buildScriptSha256 = (Get-FileHash -LiteralPath $PSCommandPath -Algorithm SHA256).Hash.ToLowerInvariant()
        toolingWorktreeChanged = [bool](& git -C $repo status --porcelain -- deployment/windows)
        sdk = $version; runtime = $sdk.runtimeVersion; distributionStatus = $lock.distributionStatus
        builtAtUtc = [DateTime]::UtcNow.ToString('o'); preflightSha256 = (Get-FileHash -LiteralPath $verifier -Algorithm SHA256).Hash.ToLowerInvariant()
        applicationPayload = 'NOT_REQUESTED'; installVerified = $false; signed = $false
    }
    $toolingFiles = @(Get-ChildItem -LiteralPath $PSScriptRoot -Recurse -File | Where-Object {
        $_.FullName -notmatch '\\(bin|obj)\\' -and $_.Extension -in @('.ps1', '.cs', '.csproj', '.json', '.manifest')
    } | Sort-Object FullName | ForEach-Object {
        [ordered]@{ path = $_.FullName.Substring($PSScriptRoot.Length + 1).Replace('\', '/'); sha256 = (Get-FileHash -LiteralPath $_.FullName -Algorithm SHA256).Hash.ToLowerInvariant() }
    })
    $toolingManifest = Join-Path $job 'tooling-manifest.json'
    ConvertTo-Json -InputObject $toolingFiles -Depth 4 | Set-Content -LiteralPath $toolingManifest -Encoding UTF8
    $report.toolingManifestSha256 = (Get-FileHash -LiteralPath $toolingManifest -Algorithm SHA256).Hash.ToLowerInvariant()
    if ($ApplicationPayload) {
        $policy = Get-Content -LiteralPath (Join-Path $PSScriptRoot 'app-files.json') -Raw | ConvertFrom-Json
        $paths = @($policy.buildDirectories) + @($policy.buildFiles)
        $tree = & git -C $repo ls-tree -r $SourceCommit -- @paths
        if ($LASTEXITCODE -ne 0 -or @($tree | Where-Object { $_ -match '^(120000|160000) ' }).Count -gt 0) {
            throw 'Source harus tanpa symlink/submodule.'
        }
        $archive = Join-Path $job 'committed-source.zip'
        Invoke-Checked 'git' (@('-C', $repo, 'archive', '--format=zip', ('--output=' + $archive), $SourceCommit, '--') + $paths)
        $source = Join-Path $job 'source'
        Expand-SafeZip $archive $source
        # Boundary for Tailwind's ignore walker; never copied to the application payload.
        $null = New-Item -ItemType Directory -Path (Join-Path $source '.git')
        foreach ($file in Get-ChildItem -LiteralPath $source -Recurse -File -Force) {
            if ($file.Name -like '.env*' -or $file.Extension -in @('.sql', '.sqlite', '.key', '.pem', '.pfx') -or $file.Name -like 'lsp-*') {
                throw 'Source commit mengandung file privat pada allowlist build.'
            }
        }
        foreach ($directory in @('bootstrap/cache', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'storage/app/public')) {
            $null = New-Item -ItemType Directory -Path (Join-Path $source $directory) -Force
        }
        Set-Location -LiteralPath $source
        Invoke-Checked 'composer.bat' @('install', '--no-dev', '--no-scripts', '--no-plugins', '--prefer-dist', '--optimize-autoloader', '--no-interaction')
        Invoke-Checked 'composer.bat' @('check-platform-reqs', '--no-dev', '--no-plugins')
        Invoke-Checked 'npm.cmd' @('ci', '--no-audit', '--no-fund')
        Invoke-Checked (Join-Path $source 'node_modules\.bin\tsc.cmd') @('--noEmit')
        Invoke-Checked (Join-Path $source 'node_modules\.bin\eslint.cmd') @('resources/js', 'vite.config.js', '--max-warnings=0')
        Invoke-Checked 'npm.cmd' @('run', 'build')
        $css = @(Get-ChildItem -LiteralPath (Join-Path $source 'public/build/assets') -Filter '*.css' | ForEach-Object { [IO.File]::ReadAllText($_.FullName) }) -join "`n"
        foreach ($utility in @('.flex{', '.grid{', '.bg-background{', '.text-sm{')) {
            if (-not $css.Contains($utility)) { throw ('CSS utilitas Tailwind tidak lengkap: ' + $utility) }
        }
        $payload = Join-Path $job 'application'
        $null = New-Item -ItemType Directory -Path $payload
        foreach ($directory in $policy.payloadDirectories) {
            Copy-Item -LiteralPath (Join-Path $source $directory) -Destination (Join-Path $payload $directory) -Recurse -Force
        }
        foreach ($file in $policy.payloadFiles) {
            $target = Join-Path $payload $file
            $null = New-Item -ItemType Directory -Path (Split-Path -Parent $target) -Force
            Copy-Item -LiteralPath (Join-Path $source $file) -Destination $target -Force
        }
        $files = @(Get-ChildItem -LiteralPath $payload -Recurse -File -Force | Sort-Object FullName | ForEach-Object {
            [ordered]@{ path = $_.FullName.Substring($payload.Length + 1).Replace('\', '/'); length = $_.Length; sha256 = (Get-FileHash -LiteralPath $_.FullName -Algorithm SHA256).Hash.ToLowerInvariant() }
        })
        $manifest = Join-Path $job 'application-manifest.json'
        [ordered]@{ schemaVersion = 1; kind = 'application-only'; sourceCommit = $SourceCommit; files = $files } |
            ConvertTo-Json -Depth 6 | Set-Content -LiteralPath $manifest -Encoding UTF8
        & (Join-Path $PSScriptRoot 'scripts\verify-payload.ps1') -PayloadRoot $payload -ManifestPath $manifest -VerifierExe $verifier
        $report.applicationPayload = 'PASS'
        $report.fileCount = $files.Count
        $report.manifestSha256 = (Get-FileHash -LiteralPath $manifest -Algorithm SHA256).Hash.ToLowerInvariant()
        $report.composerLockSha256 = (Get-FileHash -LiteralPath (Join-Path $payload 'composer.lock') -Algorithm SHA256).Hash.ToLowerInvariant()
        $report.npmLockSha256 = (Get-FileHash -LiteralPath (Join-Path $source 'package-lock.json') -Algorithm SHA256).Hash.ToLowerInvariant()
    }
    $report | ConvertTo-Json -Depth 5 | Set-Content -LiteralPath (Join-Path $job 'build-report.json') -Encoding UTF8
    Write-Output ('Build privat: ' + $job)
    Write-Output 'Bukan installer produksi. Tidak ada service/DB/certificate/firewall yang dipasang.'
} finally {
    Set-Location -LiteralPath $oldLocation
    foreach ($name in $savedEnvironment.Keys) { [Environment]::SetEnvironmentVariable($name, $savedEnvironment[$name], 'Process') }
}

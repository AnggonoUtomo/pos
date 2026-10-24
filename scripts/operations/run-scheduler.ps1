param(
    [Parameter(Mandatory = $true)][string]$PhpBinary,
    [Parameter(Mandatory = $true)][string]$MySqlDirectory
)

$ErrorActionPreference = 'Stop'
$root = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '../..')).Path
try {
    if (-not [IO.Path]::IsPathRooted($PhpBinary) -or -not [IO.Path]::IsPathRooted($MySqlDirectory)) { throw 'Absolute tool paths required.' }
    if (-not (Test-Path -LiteralPath $PhpBinary -PathType Leaf) -or -not (Test-Path -LiteralPath (Join-Path $MySqlDirectory 'mysqldump.exe') -PathType Leaf)) { throw 'Tools unavailable.' }
    $env:PATH = $MySqlDirectory + [IO.Path]::PathSeparator + $env:PATH
    Push-Location -LiteralPath $root
    try {
        & $PhpBinary artisan schedule:run *> $null
        $result = $LASTEXITCODE
    } finally {
        Pop-Location
    }
    if ($result -ne 0) { throw 'Scheduler failed.' }
    exit 0
} catch {
    Write-Error 'Runner POS gagal. Periksa tool, schema dan layanan. Detail credential tidak ditampilkan.' -ErrorAction Continue
    exit 1
}

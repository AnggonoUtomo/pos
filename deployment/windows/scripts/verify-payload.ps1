[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)][string]$PayloadRoot,
    [Parameter(Mandatory = $true)][string]$ManifestPath,
    [Parameter(Mandatory = $true)][string]$VerifierExe
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

if (-not (Test-Path -LiteralPath $VerifierExe -PathType Leaf)) {
    throw 'Build Pos.Setup terlebih dahulu. Tidak menjalankan verifier alternatif.'
}
$payload = [IO.Path]::GetFullPath($PayloadRoot).TrimEnd('\')
$manifest = [IO.Path]::GetFullPath($ManifestPath)
if ($payload.Contains('"') -or $manifest.Contains('"')) { throw 'Path mengandung quote tidak valid.' }
$logRoot = [IO.Path]::GetDirectoryName($manifest)
$id = [Guid]::NewGuid().ToString('N')
$output = Join-Path $logRoot ('verification-' + $id + '.json')
$errors = Join-Path $logRoot ('verification-' + $id + '.err')
# WinExe must be awaited explicitly; LASTEXITCODE can otherwise remain from a prior command.
$process = Start-Process -FilePath $VerifierExe -ArgumentList @('--verify-payload', ('"' + $payload + '"'), ('"' + $manifest + '"')) `
    -Wait -PassThru -WindowStyle Hidden -RedirectStandardOutput $output -RedirectStandardError $errors
if ($process.ExitCode -ne 0) { throw 'Verifikasi payload gagal. Tidak boleh dikemas; periksa report verification pada direktori manifest.' }
$result = Get-Content -LiteralPath $output -Raw | ConvertFrom-Json
if ($result.status -ne 'PASS' -or $result.kind -ne 'application-only') { throw 'Verifier tidak menghasilkan bukti PASS.' }
Write-Output ('Payload PASS: ' + $result.files + ' file.')

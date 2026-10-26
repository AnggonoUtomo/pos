param(
    [string]$ProjectRoot = (Split-Path (Split-Path $PSScriptRoot -Parent) -Parent)
)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Runtime.WindowsRuntime
Add-Type -AssemblyName System.Drawing
[Windows.Storage.StorageFile, Windows.Storage, ContentType = WindowsRuntime] | Out-Null
[Windows.Data.Pdf.PdfDocument, Windows.Data.Pdf, ContentType = WindowsRuntime] | Out-Null
[Windows.Storage.Streams.InMemoryRandomAccessStream, Windows.Storage.Streams, ContentType = WindowsRuntime] | Out-Null

function Wait-Operation($Operation, [Type]$ResultType) {
    $method = [System.WindowsRuntimeSystemExtensions].GetMethods() | Where-Object {
        $_.Name -eq 'AsTask' -and $_.IsGenericMethod -and $_.GetParameters().Count -eq 1 -and
        $_.GetParameters()[0].ParameterType.Name -eq 'IAsyncOperation`1'
    } | Select-Object -First 1
    $task = $method.MakeGenericMethod($ResultType).Invoke($null, @($Operation))
    $task.GetAwaiter().GetResult()
}

function Wait-Action($Action) {
    $method = [System.WindowsRuntimeSystemExtensions].GetMethods() | Where-Object {
        $_.Name -eq 'AsTask' -and -not $_.IsGenericMethod -and $_.GetParameters().Count -eq 1 -and
        $_.GetParameters()[0].ParameterType.Name -eq 'IAsyncAction'
    } | Select-Object -First 1
    $task = $method.Invoke($null, @($Action))
    $task.GetAwaiter().GetResult() | Out-Null
}

$root = (Resolve-Path -LiteralPath $ProjectRoot).Path
$temporary = Join-Path $root 'storage/framework/testing/user-guide'
$manifest = Get-Content -LiteralPath (Join-Path $temporary 'manifest.json') -Raw | ConvertFrom-Json
$deliveryManifestPath = Join-Path $root 'output/pdf/user-guide/manifest.json'
$deliveryManifest = Get-Content -LiteralPath $deliveryManifestPath -Raw | ConvertFrom-Json
$report = @()
foreach ($publication in $manifest.publications) {
    $result = $deliveryManifest.results | Where-Object { $_.name -eq $publication.name }
    $actualHash = (Get-FileHash -LiteralPath $publication.pdf -Algorithm SHA256).Hash.ToLowerInvariant()
    if ($result.sha256 -ne $actualHash) { throw "PDF berubah sesudah build: $($publication.name)" }
    $file = Wait-Operation ([Windows.Storage.StorageFile]::GetFileFromPathAsync($publication.pdf.Replace('/', '\'))) ([Windows.Storage.StorageFile])
    $pdf = Wait-Operation ([Windows.Data.Pdf.PdfDocument]::LoadFromFileAsync($file)) ([Windows.Data.Pdf.PdfDocument])
    $destination = Join-Path $temporary $publication.name
    New-Item -ItemType Directory -Path $destination -Force | Out-Null
    $pageReports = @()
    for ($index = 0; $index -lt $pdf.PageCount; $index++) {
        $page = $pdf.GetPage($index)
        $stream = New-Object Windows.Storage.Streams.InMemoryRandomAccessStream
        $options = New-Object Windows.Data.Pdf.PdfPageRenderOptions
        $options.DestinationWidth = 1100
        Wait-Action ($page.RenderToStreamAsync($stream, $options))
        $stream.Seek(0)
        $reader = [System.IO.WindowsRuntimeStreamExtensions]::AsStreamForRead($stream)
        $png = Join-Path $destination ('page-{0:D3}.png' -f ($index + 1))
        $output = [System.IO.File]::Create($png)
        $reader.CopyTo($output)
        $output.Dispose()
        $reader.Dispose()
        $stream.Dispose()
        $page.Dispose()
        $bitmap = [System.Drawing.Bitmap]::FromFile($png)
        $ink = 0
        for ($x = 30; $x -lt ($bitmap.Width - 30); $x += 12) {
            for ($y = 40; $y -lt ($bitmap.Height - 80); $y += 12) {
                $pixel = $bitmap.GetPixel($x, $y)
                if ($pixel.R -lt 220 -or $pixel.G -lt 220 -or $pixel.B -lt 220) { $ink++ }
            }
        }
        if ($ink -lt 10) { throw "Halaman kosong: $png" }
        $pageReports += @{ page = $index + 1; width = $bitmap.Width; height = $bitmap.Height; inkSamples = $ink }
        $bitmap.Dispose()
    }
    $report += @{ name = $publication.name; pageCount = $pdf.PageCount; pages = $pageReports }
    $result | Add-Member -NotePropertyName pageCount -NotePropertyValue $pdf.PageCount -Force
    $result | Add-Member -NotePropertyName nonblankPages -NotePropertyValue $pageReports.Count -Force
    Write-Output "PASS: $($publication.name), $($pdf.PageCount) halaman dirender, tidak kosong"
}
$json = ConvertTo-Json -InputObject $report -Depth 5
[System.IO.File]::WriteAllText((Join-Path $temporary 'pages-report.json'), $json)
[System.IO.File]::WriteAllText($deliveryManifestPath, (ConvertTo-Json -InputObject $deliveryManifest -Depth 8))

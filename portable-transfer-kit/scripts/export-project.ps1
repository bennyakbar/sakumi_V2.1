param(
    [string]$OutputRoot = ""
)

$ErrorActionPreference = "Stop"

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Resolve-Path (Join-Path $ScriptDir "..\..")
if ([string]::IsNullOrWhiteSpace($OutputRoot)) {
    $OutputRoot = Join-Path $ProjectRoot "_portable_exports"
}

$Timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$WorkDir = Join-Path $OutputRoot "sakumi-transfer-$Timestamp"
$AppDir = Join-Path $WorkDir "app"

New-Item -ItemType Directory -Force -Path $AppDir | Out-Null

Write-Host "[1/4] Copy source code..."
$excludeDirs = @(".git", "node_modules", "vendor", "_portable_exports")
Get-ChildItem -Path $ProjectRoot -Force | ForEach-Object {
    if ($excludeDirs -contains $_.Name) { return }
    Copy-Item -Path $_.FullName -Destination $AppDir -Recurse -Force
}

if (Test-Path (Join-Path $ProjectRoot ".env")) {
    Copy-Item (Join-Path $ProjectRoot ".env") (Join-Path $WorkDir ".env.transfer") -Force
}

Write-Host "[2/4] Copy storage public..."
$srcPublic = Join-Path $ProjectRoot "storage\app\public"
$dstPublic = Join-Path $WorkDir "storage-public"
New-Item -ItemType Directory -Force -Path $dstPublic | Out-Null
if (Test-Path $srcPublic) {
    Copy-Item (Join-Path $srcPublic "*") $dstPublic -Recurse -Force -ErrorAction SilentlyContinue
}

Write-Host "[3/4] Copy sqlite dummy (if exists)..."
$sqliteDummy = Join-Path $ProjectRoot "database\sakumi_dummy.sqlite"
if (Test-Path $sqliteDummy) {
    Copy-Item $sqliteDummy (Join-Path $WorkDir "database.sqlite") -Force
}

Write-Host "[4/4] Build zip archive..."
New-Item -ItemType Directory -Force -Path $OutputRoot | Out-Null
$ZipPath = Join-Path $OutputRoot "sakumi-transfer-$Timestamp.zip"
Compress-Archive -Path $WorkDir -DestinationPath $ZipPath -CompressionLevel Optimal -Force

Write-Host "Done: $ZipPath"

param(
    [Parameter(Mandatory = $true)][string]$ArchivePath,
    [Parameter(Mandatory = $true)][string]$Destination
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path $ArchivePath)) {
    throw "Archive not found: $ArchivePath"
}

$TempDir = Join-Path ([System.IO.Path]::GetTempPath()) ("sakumi-transfer-" + [System.Guid]::NewGuid().ToString("N"))
New-Item -ItemType Directory -Force -Path $TempDir | Out-Null

try {
    Write-Host "[1/4] Extract archive..."
    Expand-Archive -Path $ArchivePath -DestinationPath $TempDir -Force

    $BundleDir = Get-ChildItem -Path $TempDir -Directory | Where-Object { $_.Name -like "sakumi-transfer-*" } | Select-Object -First 1
    if (-not $BundleDir) {
        throw "Invalid bundle format."
    }

    Write-Host "[2/4] Restore app files..."
    New-Item -ItemType Directory -Force -Path $Destination | Out-Null
    Copy-Item (Join-Path $BundleDir.FullName "app\*") $Destination -Recurse -Force

    Write-Host "[3/4] Restore storage public..."
    $destPublic = Join-Path $Destination "storage\app\public"
    New-Item -ItemType Directory -Force -Path $destPublic | Out-Null
    $srcPublic = Join-Path $BundleDir.FullName "storage-public"
    if (Test-Path $srcPublic) {
        Copy-Item (Join-Path $srcPublic "*") $destPublic -Recurse -Force -ErrorAction SilentlyContinue
    }

    $envTransfer = Join-Path $BundleDir.FullName ".env.transfer"
    $envTarget = Join-Path $Destination ".env"
    if ((Test-Path $envTransfer) -and -not (Test-Path $envTarget)) {
        Copy-Item $envTransfer $envTarget -Force
    }

    $sqlite = Join-Path $BundleDir.FullName "database.sqlite"
    if (Test-Path $sqlite) {
        $dbDir = Join-Path $Destination "database"
        New-Item -ItemType Directory -Force -Path $dbDir | Out-Null
        Copy-Item $sqlite (Join-Path $dbDir "sakumi_dummy.sqlite") -Force
    }

    $sqlDump = Join-Path $BundleDir.FullName "database.sql"
    if (Test-Path $sqlDump) {
        $dbDir = Join-Path $Destination "database"
        New-Item -ItemType Directory -Force -Path $dbDir | Out-Null
        Copy-Item $sqlDump (Join-Path $dbDir "import-database.sql") -Force
    }

    Write-Host "[4/4] Done."
    Write-Host "Next steps:"
    Write-Host "1) cd $Destination"
    Write-Host "2) composer install"
    Write-Host "3) npm install; npm run build"
    Write-Host "4) php artisan key:generate --force (if APP_KEY empty)"
    Write-Host "5) php artisan migrate --force"
    Write-Host "6) php artisan storage:link"
    Write-Host "7) If available: import database\import-database.sql into DB server"
}
finally {
    if (Test-Path $TempDir) {
        Remove-Item -Path $TempDir -Recurse -Force
    }
}

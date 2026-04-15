param(
    [ValidateSet('backup', 'restore')]
    [string]$Action = 'backup',
    [string]$BackupFile = ''
)

$ErrorActionPreference = 'Stop'

$BackupDir = Join-Path $PSScriptRoot 'backups'
$DbName = 'citas_medicas'
$DbUser = 'root'
$DbPassword = 'root123'
$Container = 'citas-medicas-mysql'
$Timestamp = Get-Date -Format 'yyyyMMdd_HHmmss'

if (-not (Test-Path $BackupDir)) {
    New-Item -ItemType Directory -Path $BackupDir | Out-Null
}

function Do-Backup {
    $OutFile = Join-Path $BackupDir ("citas_medicas_{0}.sql" -f $Timestamp)
    Write-Host "Generando backup en: $OutFile"

    $dump = "docker exec $Container mysqldump -u $DbUser -p$DbPassword $DbName"
    cmd /c $dump | Out-File -FilePath $OutFile -Encoding utf8

    if (Test-Path $OutFile) {
        Write-Host "Backup completado: $OutFile"
    } else {
        throw "No se pudo crear el backup."
    }
}

function Do-Restore {
    if ([string]::IsNullOrWhiteSpace($BackupFile)) {
        throw "Debes indicar -BackupFile con la ruta del .sql"
    }

    if (-not (Test-Path $BackupFile)) {
        throw "No existe el archivo: $BackupFile"
    }

    Write-Host "Restaurando backup desde: $BackupFile"
    Get-Content -Path $BackupFile -Raw | docker exec -i $Container mysql -u $DbUser -p$DbPassword $DbName
    Write-Host "Restore completado."
}

switch ($Action) {
    'backup' { Do-Backup }
    'restore' { Do-Restore }
}

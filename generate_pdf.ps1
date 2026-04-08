$lines = @(
    'Paso 1: Crea o usa un repositorio en GitHub con tu proyecto',
    'Paso 2: Asegurate de tener el archivo Dockerfile en la raiz',
    'Paso 3: Agrega fly.toml en la raiz con app y puerto 80',
    'Paso 4: Agrega .github/workflows/fly-deploy.yml para deploy automatico',
    'Paso 5: Haz push a la rama main en GitHub',
    'Paso 6: En Fly, crea un token desde flyctl auth token o web',
    'Paso 7: En GitHub, agrega un Secret FLY_API_TOKEN con ese token',
    'Paso 8: En GitHub Actions, al hacer push se ejecutara el flujo',
    'Paso 9: Configura DB_HOST, DB_USER, DB_PASSWORD y DB_NAME en Fly',
    'Paso 10: Usa una base de datos externa como db4free.net si no tienes MySQL en Fly',
    'Paso 11: Revisa logs con flyctl logs o GitHub Actions',
    'Paso 12: Abre la URL de Fly para verificar la app'
)

$stream = "BT`n/F1 12 Tf`n50 760 Td (" + $lines[0] + ") Tj`n"
for ($i = 1; $i -lt $lines.Count; $i++) {
    $stream += "0 -16 Td (" + $lines[$i] + ") Tj`n"
}
$stream += "ET`n"

$length = [Text.Encoding]::Ascii.GetBytes($stream).Length

$pdfLines = @(
    '%PDF-1.4',
    '1 0 obj',
    '<< /Type /Catalog /Pages 2 0 R >>',
    'endobj',
    '2 0 obj',
    '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
    'endobj',
    '3 0 obj',
    '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
    'endobj',
    '4 0 obj',
    "<< /Length $length >>",
    'stream',
    $stream.TrimEnd(),
    'endstream',
    'endobj',
    '5 0 obj',
    '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
    'endobj'
)

$offsets = @()
$pos = 0
foreach ($line in $pdfLines) {
    if ($line -eq '1 0 obj' -or $line -eq '2 0 obj' -or $line -eq '3 0 obj' -or $line -eq '4 0 obj' -or $line -eq '5 0 obj') {
        $offsets += $pos
    }
    $pos += [Text.Encoding]::Ascii.GetBytes($line + "`n").Length
}

$pdfHeader = $pdfLines -join "`n" + "`n"
$pdfSize = [Text.Encoding]::Ascii.GetBytes($pdfHeader).Length

$xrefLines = @(
    'xref',
    '0 6',
    '0000000000 65535 f '
)
for ($i = 0; $i -lt $offsets.Count; $i++) {
    $xrefLines += ('{0:0000000000} 00000 n ' -f $offsets[$i])
}
$xrefLines += 'trailer'
$xrefLines += '<< /Size 6 /Root 1 0 R >>'
$xrefLines += 'startxref'
$xrefLines += $pdfSize
$xrefLines += '%%EOF'

$finalContent = $pdfLines + $xrefLines
[System.IO.File]::WriteAllText('DEPLOY_FLY_GITHUB.pdf', ($finalContent -join "`n"), [Text.Encoding]::Ascii)
Write-Host 'PDF creado: DEPLOY_FLY_GITHUB.pdf'

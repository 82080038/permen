# Upload aplikasi permen ke InfinityFree via FTP
# Jalankan: powershell -ExecutionPolicy Bypass -File upload_ftp.ps1

$ftpHost     = "ftpupload.net"
$ftpUser     = "if0_42138385"
$ftpPass     = $env:FTP_PASS  # Set via: $env:FTP_PASS = "your_password"
$ftpPort     = 21
$ftpRootPath = "/htdocs"

$localRoot = "C:\xampp\htdocs\permen"

# Folder/file yang TIDAK diupload
$excludeDirs = @('.git', 'node_modules', 'vendor', 'tests', 'test-results', 'playwright-report', 'playwright', '.github')
$excludeFiles = @('upload_ftp.ps1', 'playwright.config.js', 'package.json', 'package-lock.json', 'composer.json', 'composer.lock', 'phpunit.xml', 'phpstan.neon', '.php-cs-fixer.php')

function Should-Exclude($path) {
    foreach ($dir in $excludeDirs) {
        if ($path -match [regex]::Escape("\$dir\") -or $path -match [regex]::Escape("\$dir$")) {
            return $true
        }
    }
    return $false
}

function FTP-CreateDir($uri, $cred) {
    try {
        $req = [System.Net.FtpWebRequest]::Create($uri)
        $req.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $req.Credentials = $cred
        $req.UsePassive = $true
        $req.UseBinary = $true
        $req.KeepAlive = $false
        $resp = $req.GetResponse()
        $resp.Close()
    } catch {
        # Directory might already exist, ignore
    }
}

function FTP-UploadFile($localFile, $ftpUri, $cred) {
    try {
        $req = [System.Net.FtpWebRequest]::Create($ftpUri)
        $req.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $req.Credentials = $cred
        $req.UsePassive = $true
        $req.UseBinary = $true
        $req.KeepAlive = $false

        $fileContent = [System.IO.File]::ReadAllBytes($localFile)
        $req.ContentLength = $fileContent.Length

        $stream = $req.GetRequestStream()
        $stream.Write($fileContent, 0, $fileContent.Length)
        $stream.Close()

        $resp = $req.GetResponse()
        $resp.Close()
        return $true
    } catch {
        Write-Host "  [ERROR] $_" -ForegroundColor Red
        return $false
    }
}

# Setup credentials
$cred = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
$ftpBase = "ftp://${ftpHost}${ftpRootPath}"

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host " Upload Aplikasi Permen ke InfinityFree" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "FTP Host  : $ftpHost" -ForegroundColor Yellow
Write-Host "FTP User  : $ftpUser" -ForegroundColor Yellow
Write-Host "Target    : $ftpBase" -ForegroundColor Yellow
Write-Host ""

# Ambil semua file
$allFiles = Get-ChildItem $localRoot -Recurse -File | Where-Object {
    -not (Should-Exclude $_.FullName) -and ($excludeFiles -notcontains $_.Name)
}

$total = $allFiles.Count
$count = 0
$errors = 0

Write-Host "Total file akan diupload: $total" -ForegroundColor Green
Write-Host ""

foreach ($file in $allFiles) {
    $relativePath = $file.FullName.Substring($localRoot.Length).Replace('\', '/')
    $ftpFilePath  = $ftpBase + $relativePath
    $ftpDirPath   = $ftpBase + ($relativePath | Split-Path -Parent).Replace('\', '/')

    # Buat direktori jika perlu
    FTP-CreateDir $ftpDirPath $cred

    # Upload file
    $count++
    $pct = [math]::Round(($count / $total) * 100)
    Write-Host "[$count/$total] ($pct%) $relativePath" -ForegroundColor White

    $ok = FTP-UploadFile $file.FullName $ftpFilePath $cred
    if (-not $ok) { $errors++ }
}

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
if ($errors -eq 0) {
    Write-Host " SELESAI! $count file berhasil diupload." -ForegroundColor Green
} else {
    Write-Host " Selesai dengan $errors error dari $count file." -ForegroundColor Yellow
}
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Cek hasil di: https://bimbel.freehosting.dev" -ForegroundColor Green

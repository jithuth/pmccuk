# deploy.ps1
# PMCC-UK Direct SSH Zip Deployment Pipeline

# --- CONFIGURATION ---
$SshHost = "141.136.39.186"
$SshPort = 65002
$SshUser = "u601819832"
$SshKeyPath = "$Home\.ssh\id_ed25519_hostinger"
$RemotePath = "domains/pmccuk.org/public_html" # Adjust this to your Laravel root path on Hostinger
# ---------------------

Clear-Host
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "     PMCC-UK DIRECT SSH ZIP DEPLOYMENT PIPELINE    " -ForegroundColor Cyan -Bold
Write-Host "==================================================" -ForegroundColor Cyan

# Check if SSH key exists
if (-not (Test-Path $SshKeyPath)) {
    Write-Host "[ERROR] SSH Private Key not found at: $SshKeyPath" -ForegroundColor Red
    exit 1
}

# 1. CHECK LOCAL GIT STATUS & COMMIT IF REQUESTED
Write-Host "`n[1/5] Checking local Git status..." -ForegroundColor Yellow
$status = git status --porcelain
if ($status) {
    Write-Host "You have uncommitted changes:" -ForegroundColor DarkYellow
    git status -s
    
    $choices = [System.Management.Automation.Host.ChoiceDescription[]]@(
        New-Object System.Management.Automation.Host.ChoiceDescription "&Yes", "Commit changes now"
        New-Object System.Management.Automation.Host.ChoiceDescription "&No", "Deploy without committing"
        New-Object System.Management.Automation.Host.ChoiceDescription "&Abort", "Cancel deployment"
    )
    $decision = $host.ui.PromptForChoice("Git Changes", "Do you want to commit these changes before zipping?", $choices, 0)
    
    if ($decision -eq 2) {
        Write-Host "Deployment aborted." -ForegroundColor Red
        exit 0
    } elseif ($decision -eq 0) {
        $msg = Read-Host "Enter commit message"
        if ([string]::IsNullOrWhiteSpace($msg)) { $msg = "Deploy updates - $(Get-Date -Format 'yyyy-MM-dd HH:mm')" }
        git add -A
        git commit -m $msg
        Write-Host "Changes committed successfully." -ForegroundColor Green
    }
} else {
    Write-Host "Local working tree clean." -ForegroundColor Green
}

# 2. COMPARE CHANGES
Write-Host "`n[2/5] Analyzing modified files..." -ForegroundColor Yellow
# Show files modified in the latest commit
Write-Host "Files changed in the latest commit to be deployed:" -ForegroundColor Green
git diff-tree --no-commit-id --name-status -r HEAD

$composerChanged = $false
$migrationsChanged = $false

$changedFiles = git diff-tree --no-commit-id --name-only -r HEAD
if ($changedFiles) {
    if ($changedFiles -match 'composer\.(json|lock)') {
        $composerChanged = $true
    }
    if ($changedFiles -match 'database/migrations/') {
        $migrationsChanged = $true
    }
}

# Ask for confirmation before proceeding
$confirm = Read-Host "Proceed with creating deploy package? (Y/N)"
if ($confirm -notmatch '^[Yy]$') {
    Write-Host "Deployment cancelled." -ForegroundColor Red
    exit 0
}

# 3. CREATE ZIP ARCHIVE
Write-Host "`n[3/5] Packing deployment archive..." -ForegroundColor Yellow
$ZipFile = "deploy_package.zip"

if (Test-Path $ZipFile) {
    Remove-Item $ZipFile -Force
}

# Use git archive to bundle ONLY git-tracked files (ignores vendor, node_modules, .env, storage, etc.)
git archive -o $ZipFile HEAD
if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERROR] Failed to create git archive." -ForegroundColor Red
    exit 1
}
Write-Host "Created deployment archive: $ZipFile ($(Get-Item $ZipFile | select -expand length | ForEach-Object { [Math]::Round($_ / 1MB, 2) }) MB)" -ForegroundColor Green

# 4. UPLOAD VIA SCP
Write-Host "`n[4/5] Uploading deployment package to Hostinger..." -ForegroundColor Yellow
Write-Host "Uploading $ZipFile to $SshHost on port $SshPort..." -ForegroundColor DarkGray

$ScpArgs = @(
    "-P", $SshPort,
    "-i", $SshKeyPath,
    "-o", "StrictHostKeyChecking=no",
    $ZipFile,
    "${SshUser}@${SshHost}:${RemotePath}/deploy_package.zip"
)

& scp $ScpArgs

if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERROR] File upload failed." -ForegroundColor Red
    Write-Host "Please ensure:" -ForegroundColor DarkYellow
    Write-Host "  1. Your current IP is whitelisted in your Hostinger control panel (hPanel)." -ForegroundColor DarkYellow
    Write-Host "  2. SSH access is enabled in Hostinger." -ForegroundColor DarkYellow
    Remove-Item $ZipFile -Force
    exit 1
}
Write-Host "Deployment package uploaded successfully." -ForegroundColor Green
Remove-Item $ZipFile -Force

# 5. REMOTE EXTRACT & DEPLOY
Write-Host "`n[5/5] Extracting archive and executing remote post-deploy tasks..." -ForegroundColor Yellow
Write-Host "Connecting via SSH..." -ForegroundColor DarkGray

$composerVal = if ($composerChanged) { "true" } else { "false" }
$migrateVal = if ($migrationsChanged) { "true" } else { "false" }

# Build remote command script
$RemoteCommands = @"
RUN_COMPOSER=\$1
RUN_MIGRATE=\$2

cd $RemotePath || { echo "Directory not found: $RemotePath"; exit 1; }

echo "==> Extracting deployment package..."
unzip -o deploy_package.zip
rm deploy_package.zip

if [ "\$RUN_COMPOSER" = "true" ]; then
    echo "==> composer.json/lock changes detected. Installing dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction
else
    echo "==> No composer changes. Skipping dependency installation."
fi

if [ "\$RUN_MIGRATE" = "true" ]; then
    echo "==> New migrations detected. Running migrations..."
    php artisan migrate --force
else
    echo "==> No migration changes. Skipping database migrations."
fi

echo "==> Refreshing application optimization & clearing cache..."
php artisan optimize:clear

echo "==> Deployment Complete!"
"@

# Write temporary commands file to host SSH (converting CRLF to LF for Unix Bash compatibility)
$TempFile = [System.IO.Path]::GetTempFileName()
$UnixCommands = $RemoteCommands -replace "`r`n", "`n"
[System.IO.File]::WriteAllText($TempFile, $UnixCommands)

# Run ssh feeding the command string using CMD input redirection to bypass PowerShell's automatic CRLF pipeline conversion
$CmdLine = "ssh -o StrictHostKeyChecking=no -i `"$SshKeyPath`" -p $SshPort $SshUser@$SshHost bash -s -- $composerVal $migrateVal < `"$TempFile`""
cmd.exe /c $CmdLine

$SshExit = $LASTEXITCODE
Remove-Item $TempFile -Force

if ($SshExit -ne 0) {
    Write-Host "`n[ERROR] Remote execution failed." -ForegroundColor Red
    exit 1
}

Write-Host "`n==================================================" -ForegroundColor Green
Write-Host "   Deployment successfully executed via Direct SSH! " -ForegroundColor Green -Bold
Write-Host "==================================================" -ForegroundColor Green

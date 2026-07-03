# deploy.ps1
# PMCC-UK Deployment Pipeline Script

# --- CONFIGURATION ---
$SshHost = "srv1700928.hstgr.cloud"
$SshPort = 65002
$SshUser = "u601819832"
$SshKeyPath = "$Home\.ssh\id_ed25519_hostinger"
$RemotePath = "domains/pmccuk.org/public_html"
$GitRemote = "pmccuk"
$GitBranch = "main"
# ---------------------

Clear-Host
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "       PMCC-UK SSH DEPLOYMENT PIPELINE            " -ForegroundColor Cyan -Bold
Write-Host "==================================================" -ForegroundColor Cyan

# Check if SSH key exists
if (-not (Test-Path $SshKeyPath)) {
    Write-Host "[ERROR] SSH Private Key not found at: $SshKeyPath" -ForegroundColor Red
    exit 1
}

# 1. CHECK LOCAL GIT STATUS
Write-Host "`n[1/5] Checking local Git status..." -ForegroundColor Yellow
$status = git status --porcelain
if ($status) {
    Write-Host "You have uncommitted changes:" -ForegroundColor DarkYellow
    git status -s
    
    $choices = [System.Management.Automation.Host.ChoiceDescription[]]@(
        New-Object System.Management.Automation.Host.ChoiceDescription "&Yes", "Commit changes now"
        New-Object System.Management.Automation.Host.ChoiceDescription "&No", "Deploy without committing them"
        New-Object System.Management.Automation.Host.ChoiceDescription "&Abort", "Cancel deployment"
    )
    $decision = $host.ui.PromptForChoice("Git Changes", "Do you want to commit these changes before deploying?", $choices, 0)
    
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
Write-Host "`n[2/5] Comparing changes with remote repository..." -ForegroundColor Yellow
Write-Host "Fetching latest from $GitRemote..." -ForegroundColor DarkGray
git fetch $GitRemote

$composerChanged = $false
$migrationsChanged = $false

# Check difference
$commits = git log "$GitRemote/$GitBranch..HEAD" --oneline
if ($commits) {
    Write-Host "The following commits will be pushed and deployed:" -ForegroundColor Green
    $commits | ForEach-Object { Write-Host "  * $_" -ForegroundColor Green }
    
    # Show modified files list
    Write-Host "`nFiles changed in this deploy:" -ForegroundColor Green
    git diff --name-status "$GitRemote/$GitBranch..HEAD"
    
    $changedFiles = git diff --name-only "$GitRemote/$GitBranch..HEAD"
    if ($changedFiles) {
        if ($changedFiles -match 'composer\.(json|lock)') {
            $composerChanged = $true
        }
        if ($changedFiles -match 'database/migrations/') {
            $migrationsChanged = $true
        }
    }
} else {
    Write-Host "No new commits to push. Remote is up to date. Re-deploying current branch state." -ForegroundColor Yellow
    # Force run checks as safe fallback
    $composerChanged = $true
    $migrationsChanged = $true
}

# Ask for confirmation before proceeding
$confirm = Read-Host "Proceed with deployment? (Y/N)"
if ($confirm -notmatch '^[Yy]$') {
    Write-Host "Deployment cancelled." -ForegroundColor Red
    exit 0
}

# 3. PUSH TO GITHUB
Write-Host "`n[3/5] Pushing changes to GitHub..." -ForegroundColor Yellow
git push $GitRemote $GitBranch
if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERROR] Failed to push changes to GitHub. Aborting deployment." -ForegroundColor Red
    exit 1
}
Write-Host "GitHub repository updated successfully." -ForegroundColor Green

# 4. REMOTE DEPLOY OVER SSH
Write-Host "`n[4/5] Executing deployment on Hostinger server..." -ForegroundColor Yellow
Write-Host "Connecting to $SshUser@$SshHost on port $SshPort..." -ForegroundColor DarkGray

$composerVal = if ($composerChanged) { "true" } else { "false" }
$migrateVal = if ($migrationsChanged) { "true" } else { "false" }

# Build remote command script
$RemoteCommands = @"
RUN_COMPOSER=\$1
RUN_MIGRATE=\$2

cd $RemotePath || { echo "Directory not found: $RemotePath"; exit 1; }
echo "==> Pulling latest changes from Git..."
git pull || git pull $GitRemote $GitBranch

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

# Write temporary commands file to host SSH
$TempFile = [System.IO.Path]::GetTempFileName()
$RemoteCommands | Out-File -FilePath $TempFile -Encoding utf8

# Execute remote commands using ssh
$SshArgs = @(
    "-o", "StrictHostKeyChecking=no",
    "-i", $SshKeyPath,
    "-p", $SshPort,
    "$SshUser@$SshHost",
    "bash -s", "--", $composerVal, $migrateVal
)

# Run ssh feeding the command string
Get-Content $TempFile | & ssh $SshArgs

$SshExit = $LASTEXITCODE
Remove-Item $TempFile -Force

if ($SshExit -ne 0) {
    Write-Host "`n[ERROR] SSH deployment failed." -ForegroundColor Red
    Write-Host "Please ensure:" -ForegroundColor DarkYellow
    Write-Host "  1. Your current IP address is whitelisted in your Hostinger control panel (hPanel)." -ForegroundColor DarkYellow
    Write-Host "  2. SSH access is enabled in Hostinger." -ForegroundColor DarkYellow
    Write-Host "  3. The key path '$SshKeyPath' is correct." -ForegroundColor DarkYellow
    exit 1
}

# 5. SUMMARY
Write-Host "`n[5/5] Pipeline execution finished!" -ForegroundColor Yellow
Write-Host "==================================================" -ForegroundColor Green
Write-Host "   Deployment successfully executed via SSH!      " -ForegroundColor Green -Bold
Write-Host "==================================================" -ForegroundColor Green

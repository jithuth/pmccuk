# whatsapp-daemon.ps1
# PMCC-UK WhatsApp Daemon Remote Manager (Hostinger Production)

param (
    [ValidateSet("status", "start", "restart", "stop", "logs")]
    [string]$Action = "status"
)

$SshHost = "141.136.39.186"
$SshPort = 65002
$SshUser = "u601819832"
$SshKeyPath = "$Home\.ssh\id_ed25519_hostinger"
$DaemonPath = "domains/pmccuk.org/public_html/whatsapp-server"

if (-not (Test-Path $SshKeyPath)) {
    Write-Host "[ERROR] SSH Key not found at $SshKeyPath" -ForegroundColor Red
    exit 1
}

Write-Host "==================================================" -ForegroundColor Cyan
Write-Host " PMCC-UK WhatsApp Daemon Manager -> Production   " -ForegroundColor Cyan -Bold
Write-Host " Action: $Action" -ForegroundColor Yellow
Write-Host "==================================================" -ForegroundColor Cyan

switch ($Action) {
    "status" {
        $Cmd = "cd $DaemonPath && ./node_modules/.bin/pm2 status && curl -s -H 'Authorization: Bearer pmcc_wa_sec_key_2026_x9' http://127.0.0.1:8085/session/status"
    }
    "start" {
        $Cmd = "cd $DaemonPath && ./node_modules/.bin/pm2 start ecosystem.config.js && ./node_modules/.bin/pm2 save"
    }
    "restart" {
        $Cmd = "cd $DaemonPath && ./node_modules/.bin/pm2 restart pmcc-whatsapp-daemon && ./node_modules/.bin/pm2 save"
    }
    "stop" {
        $Cmd = "cd $DaemonPath && ./node_modules/.bin/pm2 stop pmcc-whatsapp-daemon && ./node_modules/.bin/pm2 save"
    }
    "logs" {
        $Cmd = "cd $DaemonPath && ./node_modules/.bin/pm2 logs pmcc-whatsapp-daemon --lines 40 --nostream"
    }
}

ssh -o StrictHostKeyChecking=no -i "$SshKeyPath" -p $SshPort ${SshUser}@${SshHost} "$Cmd"

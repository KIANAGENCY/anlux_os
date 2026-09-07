# Requires: railway login already done
# Creates MySQL on Railway, imports local dump, sets Vercel env, redeploys.

$ErrorActionPreference = 'Stop'
$env:VERCEL_TELEMETRY_DISABLED = '1'
$mysql = 'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe'
$dump = 'storage\logs\anlux_os_schema_data.sql'

Write-Host '== Railway whoami =='
railway whoami

Write-Host '== Create / link project =='
if (-not (Test-Path .railway)) {
  railway init -n anlux-os-db --yes 2>&1
}

Write-Host '== Add MySQL =='
railway add --database mysql --yes 2>&1

Write-Host '== Wait for MYSQL_URL =='
$vars = railway variables --json 2>&1 | Out-String
Write-Host $vars

# Parse connection from railway
$json = railway variables --json | ConvertFrom-Json
# Variables may be nested; try common keys
$url = $null
foreach ($k in @('MYSQL_URL','DATABASE_URL','MYSQL_PRIVATE_URL')) {
  if ($json.$k) { $url = [string]$json.$k; break }
  if ($json.PSObject.Properties.Name -contains $k) { $url = [string]$json.$k; break }
}

if (-not $url) {
  # flatten
  $url = railway variables 2>&1 | Select-String -Pattern 'mysql://' | Select-Object -First 1
  $url = [string]$url
}

Write-Host "URL found length: $($url.Length)"
if (-not $url -or $url -notmatch 'mysql') { throw 'No MYSQL_URL from Railway yet. Open the service in dashboard and retry.' }

# Parse mysql://user:pass@host:port/db
if ($url -match 'mysql://([^:]+):([^@]+)@([^:/]+):(\d+)/(.+)') {
  $dbUser = $Matches[1]
  $dbPass = [uri]::UnescapeDataString($Matches[2])
  $dbHost = $Matches[3]
  $dbPort = $Matches[4]
  $dbName = $Matches[5].Split('?')[0]
} else { throw "Cannot parse MYSQL_URL" }

Write-Host "Host=$dbHost Port=$dbPort Db=$dbName User=$dbUser"

Write-Host '== Import dump =='
Get-Content $dump -Raw | & $mysql -h $dbHost -P $dbPort -u $dbUser "-p$dbPass" --ssl-mode=REQUIRED 2>&1

Write-Host '== Set Vercel env =='
function Set-Ve([string]$n,[string]$v) {
  foreach ($e in @('production','preview')) {
    $v | npx --yes vercel env add $n $e --force 2>$null | Out-Null
  }
  Write-Host "set $n"
}
Set-Ve 'DB_CONNECTION' 'mysql'
Set-Ve 'DB_HOST' $dbHost
Set-Ve 'DB_PORT' $dbPort
Set-Ve 'DB_DATABASE' $dbName
Set-Ve 'DB_USERNAME' $dbUser
Set-Ve 'DB_PASSWORD' $dbPass
Set-Ve 'SESSION_DRIVER' 'database'
Set-Ve 'CACHE_STORE' 'database'

Write-Host '== Redeploy =='
npx --yes vercel deploy --yes --prod
Write-Host 'DONE'

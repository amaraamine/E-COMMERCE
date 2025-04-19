# PowerShell script to check and fix database connection for Symfony project

# Default database parameters
$defaultHost = "127.0.0.1"
$defaultPort = 3306
$defaultDbName = "your_db_name"
$defaultUser = "your_db_user"
$defaultPassword = "your_db_password"

function Test-Port {
    param (
        [string]$host,
        [int]$port
    )
    try {
        $tcpClient = New-Object System.Net.Sockets.TcpClient
        $asyncResult = $tcpClient.BeginConnect($host, $port, $null, $null)
        $wait = $asyncResult.AsyncWaitHandle.WaitOne(3000, $false)
        if (!$wait) {
            return $false
        }
        $tcpClient.EndConnect($asyncResult)
        $tcpClient.Close()
        return $true
    } catch {
        return $false
    }
}

Write-Host "Checking database connection on $defaultHost:$defaultPort ..."
$portOpen = Test-Port -host $defaultHost -port $defaultPort

if ($portOpen) {
    Write-Host "Database port $defaultPort is open."
} else {
    Write-Host "Database port $defaultPort is NOT open."
    Write-Host "Please ensure your database server is running."
}

# Prompt user for database connection parameters
$dbHost = Read-Host "Enter database host (default: $defaultHost)"
if ([string]::IsNullOrWhiteSpace($dbHost)) { $dbHost = $defaultHost }

$dbPortInput = Read-Host "Enter database port (default: $defaultPort)"
if ([string]::IsNullOrWhiteSpace($dbPortInput)) { $dbPort = $defaultPort } else { $dbPort = [int]$dbPortInput }

$dbName = Read-Host "Enter database name (default: $defaultDbName)"
if ([string]::IsNullOrWhiteSpace($dbName)) { $dbName = $defaultDbName }

$dbUser = Read-Host "Enter database user (default: $defaultUser)"
if ([string]::IsNullOrWhiteSpace($dbUser)) { $dbUser = $defaultUser }

$dbPassword = Read-Host "Enter database password (default: $defaultPassword)"
if ([string]::IsNullOrWhiteSpace($dbPassword)) { $dbPassword = $defaultPassword }

# Construct DATABASE_URL
$databaseUrl = "mysql://$dbUser`:$dbPassword@$dbHost`:$dbPort/$dbName"

# Update .env file
$envFilePath = ".env"
if (Test-Path $envFilePath) {
    $envContent = Get-Content $envFilePath
    $newContent = @()
    $found = $false
    foreach ($line in $envContent) {
        if ($line -match "^DATABASE_URL=") {
            $newContent += "DATABASE_URL=`"$databaseUrl`""
            $found = $true
        } else {
            $newContent += $line
        }
    }
    if (-not $found) {
        $newContent += "DATABASE_URL=`"$databaseUrl`""
    }
    $newContent | Set-Content $envFilePath
    Write-Host ".env file updated with new DATABASE_URL."
} else {
    Write-Host ".env file not found. Creating new .env file."
    "DATABASE_URL=`"$databaseUrl`"" | Set-Content $envFilePath
}

Write-Host "Please try running your Symfony command again."

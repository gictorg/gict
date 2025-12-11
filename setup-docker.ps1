# GICT Application - Docker Setup Script for Windows
# Run this script in PowerShell: .\setup-docker.ps1

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "GICT Application - Docker Setup" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if Docker is running
Write-Host "[1/5] Checking Docker..." -ForegroundColor Yellow
try {
    docker version | Out-Null
    Write-Host "Docker is running!" -ForegroundColor Green
} catch {
    Write-Host "ERROR: Docker is not running!" -ForegroundColor Red
    Write-Host "Please start Docker Desktop and try again." -ForegroundColor Red
    exit 1
}

# Check if docker-compose is available
Write-Host ""
Write-Host "[2/5] Checking Docker Compose..." -ForegroundColor Yellow
try {
    docker-compose version | Out-Null
    Write-Host "Docker Compose is available!" -ForegroundColor Green
} catch {
    Write-Host "WARNING: docker-compose not found. Using 'docker compose' instead." -ForegroundColor Yellow
    $useDockerCompose = $false
}

# Stop existing containers if any
Write-Host ""
Write-Host "[3/5] Stopping existing containers..." -ForegroundColor Yellow
if ($useDockerCompose) {
    docker-compose down 2>$null
} else {
    docker compose down 2>$null
}
Write-Host "Cleanup complete!" -ForegroundColor Green

# Build and start containers
Write-Host ""
Write-Host "[4/5] Building and starting containers..." -ForegroundColor Yellow
Write-Host "This may take a few minutes on first run..." -ForegroundColor Gray

if ($useDockerCompose) {
    docker-compose up -d --build
} else {
    docker compose up -d --build
}

if ($LASTEXITCODE -eq 0) {
    Write-Host "Containers started successfully!" -ForegroundColor Green
} else {
    Write-Host "ERROR: Failed to start containers!" -ForegroundColor Red
    exit 1
}

# Wait for services to be ready
Write-Host ""
Write-Host "[5/5] Waiting for services to be ready..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

# Check health
Write-Host ""
Write-Host "Checking application health..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://localhost:8080/health-simple.php" -TimeoutSec 5 -UseBasicParsing
    if ($response.StatusCode -eq 200) {
        Write-Host "Application is healthy!" -ForegroundColor Green
    } else {
        Write-Host "WARNING: Application returned status code $($response.StatusCode)" -ForegroundColor Yellow
    }
} catch {
    Write-Host "WARNING: Could not reach health endpoint. Services may still be starting." -ForegroundColor Yellow
    Write-Host "Wait a few more seconds and try: http://localhost:8080/health.php" -ForegroundColor Gray
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Setup Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Application URLs:" -ForegroundColor Cyan
Write-Host "  Main App:    http://localhost:8080/" -ForegroundColor White
Write-Host "  Health Check: http://localhost:8080/health.php" -ForegroundColor White
Write-Host ""
Write-Host "Useful Commands:" -ForegroundColor Cyan
Write-Host "  View logs:    docker-compose logs -f" -ForegroundColor White
Write-Host "  Stop:        docker-compose down" -ForegroundColor White
Write-Host "  Restart:     docker-compose restart" -ForegroundColor White
Write-Host "  Remove all:  docker-compose down -v" -ForegroundColor White
Write-Host ""


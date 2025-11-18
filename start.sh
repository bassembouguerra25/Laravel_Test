#!/bin/bash

# Event Booking System - Start Script
# This script builds and prepares the project for execution

set -e  # Exit on error

echo "🚀 Starting Event Booking System..."
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker and try again."
    exit 1
fi

# Check if docker-compose is available
if ! command -v docker compose &> /dev/null; then
    echo "❌ docker compose is not installed. Please install Docker Compose."
    exit 1
fi

echo -e "${BLUE}📦 Step 1: Checking Composer dependencies...${NC}"
if [ ! -d "vendor" ]; then
    echo "  Installing Composer dependencies..."
    docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php83-composer:latest composer install
else
    echo -e "${GREEN}✓ Dependencies already installed${NC}"
fi

echo ""
echo -e "${BLUE}📦 Step 2: Checking Docker containers...${NC}"
# Check if containers are already running
CONTAINERS_STARTED=false
if docker compose ps | grep -q "Up"; then
    echo -e "${GREEN}✓ Containers are already running${NC}"
    echo -e "${YELLOW}ℹ️  To rebuild containers from scratch, run: docker compose build --no-cache${NC}"
else
    echo -e "${BLUE}  Building Docker containers (using cache)...${NC}"
    # Only build if images don't exist or docker-compose.yaml changed
    docker compose build
    
    echo ""
    echo -e "${BLUE}🐳 Step 3: Starting Docker containers...${NC}"
    docker compose up -d
    CONTAINERS_STARTED=true
    
    echo ""
    echo -e "${YELLOW}⏳ Waiting for services to be ready...${NC}"
    sleep 10
fi

# Wait for MySQL to be ready
echo -e "${BLUE}🔍 Step 4: Waiting for MySQL to be ready...${NC}"
max_attempts=30
attempt=0
while [ $attempt -lt $max_attempts ]; do
    if docker compose exec -T mysql mysqladmin ping -h localhost --silent 2>/dev/null; then
        echo -e "${GREEN}✓ MySQL is ready!${NC}"
        break
    fi
    attempt=$((attempt + 1))
    echo "  Attempt $attempt/$max_attempts..."
    sleep 2
done

if [ $attempt -eq $max_attempts ]; then
    echo "❌ MySQL failed to start. Please check the logs: docker compose logs mysql"
    exit 1
fi

# Wait for Redis to be ready
echo -e "${BLUE}🔍 Step 5: Waiting for Redis to be ready...${NC}"
max_attempts=30
attempt=0
while [ $attempt -lt $max_attempts ]; do
    if docker compose exec -T redis redis-cli ping 2>/dev/null | grep -q PONG; then
        echo -e "${GREEN}✓ Redis is ready!${NC}"
        break
    fi
    attempt=$((attempt + 1))
    echo "  Attempt $attempt/$max_attempts..."
    sleep 2
done

if [ $attempt -eq $max_attempts ]; then
    echo "❌ Redis failed to start. Please check the logs: docker compose logs redis"
    exit 1
fi

echo ""
echo -e "${BLUE}🔑 Step 6: Generating application key...${NC}"
docker compose exec -T laravel.test php artisan key:generate --force || true

echo ""
echo -e "${BLUE}🗄️  Step 7: Running database migrations...${NC}"
docker compose exec -T laravel.test php artisan migrate --force

echo ""
echo -e "${BLUE}🌱 Step 8: Seeding database...${NC}"
docker compose exec -T laravel.test php artisan db:seed --force

echo ""
echo -e "${BLUE}🧹 Step 9: Clearing caches...${NC}"
docker compose exec -T laravel.test php artisan config:clear
docker compose exec -T laravel.test php artisan cache:clear
docker compose exec -T laravel.test php artisan route:clear
docker compose exec -T laravel.test php artisan view:clear

echo ""
echo -e "${GREEN}✅ Project is ready!${NC}"
echo ""
echo "📋 Service URLs:"
echo "   - Application: http://localhost"
echo "   - API: http://localhost/api"
echo "   - phpMyAdmin: http://localhost:8080"
echo ""
echo "📝 To view logs: docker compose logs -f"
echo "🛑 To stop: ./stop.sh"
echo ""


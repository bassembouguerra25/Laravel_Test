#!/bin/bash

# Event Booking System - Stop Script
# This script stops all Docker containers

set -e  # Exit on error

echo "🛑 Stopping Event Booking System..."
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if docker-compose is available
if ! command -v docker compose &> /dev/null; then
    echo "❌ docker compose is not installed."
    exit 1
fi

echo -e "${BLUE}🐳 Stopping Docker containers...${NC}"
docker compose down

echo ""
echo -e "${GREEN}✅ All services stopped!${NC}"
echo ""
echo "💡 To remove volumes (database data will be lost): docker compose down -v"
echo "💡 To start again: ./start.sh"
echo ""


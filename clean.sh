#!/bin/bash

# Event Booking System - Clean Script
# This script cleans temporary files, caches, and logs from the project

set -e # Exit on error

echo "🧹 Cleaning Event Booking System project..."
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if we're in a Laravel project directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Error: This doesn't appear to be a Laravel project directory.${NC}"
    echo "Please run this script from the project root directory."
    exit 1
fi

echo -e "${BLUE}📦 Step 1: Clearing Laravel caches...${NC}"
if [ -f "artisan" ]; then
    php artisan config:clear 2>/dev/null || true
    php artisan cache:clear 2>/dev/null || true
    php artisan route:clear 2>/dev/null || true
    php artisan view:clear 2>/dev/null || true
    php artisan event:clear 2>/dev/null || true
    echo -e "${GREEN}✓ Laravel caches cleared${NC}"
else
    echo -e "${YELLOW}⚠ Artisan not found, skipping Laravel cache commands${NC}"
fi

echo ""
echo -e "${BLUE}🗑️  Step 2: Removing log files...${NC}"
if [ -d "storage/logs" ]; then
    find storage/logs -name "*.log" -type f -delete 2>/dev/null || true
    echo -e "${GREEN}✓ Log files removed${NC}"
else
    echo -e "${YELLOW}⚠ storage/logs directory not found${NC}"
fi

echo ""
echo -e "${BLUE}🗑️  Step 3: Removing compiled cache files...${NC}"
if [ -d "bootstrap/cache" ]; then
    find bootstrap/cache -name "*.php" ! -name ".gitignore" -type f -delete 2>/dev/null || true
    echo -e "${GREEN}✓ Compiled cache files removed${NC}"
else
    echo -e "${YELLOW}⚠ bootstrap/cache directory not found${NC}"
fi

echo ""
echo -e "${BLUE}🗑️  Step 4: Removing framework cache files...${NC}"
# Remove cache files but keep directories
if [ -d "storage/framework/cache" ]; then
    find storage/framework/cache -mindepth 2 -type f -delete 2>/dev/null || true
    echo -e "${GREEN}✓ Framework cache files removed${NC}"
fi

if [ -d "storage/framework/sessions" ]; then
    find storage/framework/sessions -mindepth 2 -type f -delete 2>/dev/null || true
    echo -e "${GREEN}✓ Session files removed${NC}"
fi

if [ -d "storage/framework/views" ]; then
    find storage/framework/views -name "*.php" -type f -delete 2>/dev/null || true
    echo -e "${GREEN}✓ Compiled views removed${NC}"
fi

echo ""
echo -e "${BLUE}🗑️  Step 5: Removing PHPUnit cache files...${NC}"
if [ -f ".phpunit.result.cache" ]; then
    rm -f .phpunit.result.cache
    echo -e "${GREEN}✓ PHPUnit result cache removed${NC}"
fi

if [ -d ".phpunit.cache" ]; then
    rm -rf .phpunit.cache
    echo -e "${GREEN}✓ PHPUnit cache directory removed${NC}"
fi

echo ""
echo -e "${BLUE}🗑️  Step 6: Removing frontend build files...${NC}"
if [ -d "public/build" ]; then
    rm -rf public/build
    echo -e "${GREEN}✓ Frontend build files removed${NC}"
fi

if [ -f "public/hot" ]; then
    rm -f public/hot
    echo -e "${GREEN}✓ Hot reload file removed${NC}"
fi

echo ""
echo -e "${BLUE}🗑️  Step 7: Removing Node.js build files...${NC}"
if [ -d "node_modules" ]; then
    read -p "Do you want to remove node_modules? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        rm -rf node_modules
        echo -e "${GREEN}✓ node_modules removed${NC}"
    else
        echo -e "${YELLOW}⚠ node_modules kept${NC}"
    fi
fi

if [ -f "package-lock.json" ]; then
    read -p "Do you want to remove package-lock.json? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        rm -f package-lock.json
        echo -e "${GREEN}✓ package-lock.json removed${NC}"
    else
        echo -e "${YELLOW}⚠ package-lock.json kept${NC}"
    fi
fi

echo ""
echo -e "${BLUE}🗑️  Step 8: Removing Composer cache files...${NC}"
if [ -d "vendor" ]; then
    read -p "Do you want to remove vendor directory? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        rm -rf vendor
        echo -e "${GREEN}✓ vendor directory removed${NC}"
        echo -e "${YELLOW}⚠ Run 'composer install' to restore dependencies${NC}"
    else
        echo -e "${YELLOW}⚠ vendor directory kept${NC}"
    fi
fi

if [ -f "composer.lock" ]; then
    read -p "Do you want to remove composer.lock? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        rm -f composer.lock
        echo -e "${GREEN}✓ composer.lock removed${NC}"
    else
        echo -e "${YELLOW}⚠ composer.lock kept${NC}"
    fi
fi

echo ""
echo -e "${BLUE}🗑️  Step 9: Removing IDE and editor files...${NC}"
if [ -d ".idea" ]; then
    rm -rf .idea
    echo -e "${GREEN}✓ .idea directory removed${NC}"
fi

if [ -d ".vscode" ]; then
    rm -rf .vscode
    echo -e "${GREEN}✓ .vscode directory removed${NC}"
fi

if [ -f ".DS_Store" ]; then
    find . -name ".DS_Store" -type f -delete 2>/dev/null || true
    echo -e "${GREEN}✓ .DS_Store files removed${NC}"
fi

echo ""
echo -e "${GREEN}✅ Project cleaned successfully!${NC}"
echo ""
echo "📋 Summary:"
echo "   - Laravel caches cleared"
echo "   - Log files removed"
echo "   - Compiled cache files removed"
echo "   - Framework cache files removed"
echo "   - PHPUnit cache files removed"
echo "   - Frontend build files removed"
echo ""
echo "💡 To rebuild:"
echo "   - Run 'composer install' if vendor was removed"
echo "   - Run 'npm install' if node_modules was removed"
echo "   - Run './start.sh' to rebuild and start the project"
echo ""


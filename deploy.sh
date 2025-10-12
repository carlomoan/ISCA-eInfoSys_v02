#!/bin/bash
# ================================================
# DEPLOYMENT SCRIPT FOR ISCA e-InfoSys
# ================================================
# This script helps deploy the system to production server
# Usage: ./deploy.sh [environment]
# Example: ./deploy.sh production
# ================================================

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default environment
ENV=${1:-production}

echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}  ISCA e-InfoSys Deployment Script${NC}"
echo -e "${BLUE}  Environment: ${ENV}${NC}"
echo -e "${BLUE}================================================${NC}"

# Step 1: Check if .env file exists
echo -e "\n${YELLOW}[1/8] Checking environment configuration...${NC}"
if [ ! -f ".env.${ENV}" ]; then
    echo -e "${RED}Error: .env.${ENV} file not found!${NC}"
    exit 1
fi

# Step 2: Backup current .env if exists
if [ -f ".env" ]; then
    echo -e "${YELLOW}[2/8] Backing up current .env file...${NC}"
    cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
    echo -e "${GREEN}✓ Backup created${NC}"
else
    echo -e "${YELLOW}[2/8] No existing .env file to backup${NC}"
fi

# Step 3: Copy environment file
echo -e "${YELLOW}[3/8] Applying ${ENV} configuration...${NC}"
cp ".env.${ENV}" .env
echo -e "${GREEN}✓ Configuration applied${NC}"

# Step 4: Create necessary directories
echo -e "${YELLOW}[4/8] Creating required directories...${NC}"
mkdir -p uploads/field_data uploads/reports uploads/survey_data
mkdir -p logs cache backups temp
chmod 755 uploads logs cache backups temp
chmod 755 uploads/field_data uploads/reports uploads/survey_data
echo -e "${GREEN}✓ Directories created${NC}"

# Step 5: Set file permissions
echo -e "${YELLOW}[5/8] Setting file permissions...${NC}"
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod 644 .env .env.* 2>/dev/null || true
echo -e "${GREEN}✓ Permissions set${NC}"

# Step 6: Clear cache
echo -e "${YELLOW}[6/8] Clearing cache...${NC}"
if [ -d "cache" ]; then
    rm -rf cache/*
    echo -e "${GREEN}✓ Cache cleared${NC}"
else
    echo -e "${YELLOW}  No cache directory found${NC}"
fi

# Step 7: Check composer dependencies
echo -e "${YELLOW}[7/8] Checking dependencies...${NC}"
if [ -f "composer.json" ]; then
    if command -v composer &> /dev/null; then
        echo -e "${BLUE}  Installing/updating Composer dependencies...${NC}"
        composer install --no-dev --optimize-autoloader
        echo -e "${GREEN}✓ Dependencies installed${NC}"
    else
        echo -e "${YELLOW}  Composer not found, skipping dependency check${NC}"
    fi
else
    echo -e "${YELLOW}  No composer.json found, skipping${NC}"
fi

# Step 8: Run database optimizations
echo -e "${YELLOW}[8/8] Database optimizations...${NC}"
if [ -f "database_optimizations.sql" ]; then
    echo -e "${BLUE}  Database optimization file found${NC}"
    echo -e "${YELLOW}  Please run: mysql -u [user] -p [database] < database_optimizations.sql${NC}"
else
    echo -e "${YELLOW}  No database optimization file found${NC}"
fi

# Final verification
echo -e "\n${BLUE}================================================${NC}"
echo -e "${GREEN}✓ Deployment Complete!${NC}"
echo -e "${BLUE}================================================${NC}"

# Show current configuration
echo -e "\n${YELLOW}Current Configuration:${NC}"
echo -e "  Environment: $(grep APP_ENV .env | cut -d'=' -f2)"
echo -e "  Debug Mode: $(grep APP_DEBUG .env | cut -d'=' -f2)"
echo -e "  Base URL: $(grep BASE_URL .env | cut -d'=' -f2)"
echo -e "  Database: $(grep DB_NAME .env | cut -d'=' -f2)"

echo -e "\n${YELLOW}Next Steps:${NC}"
echo -e "  1. Verify database connection"
echo -e "  2. Run database migrations if needed"
echo -e "  3. Test the application"
echo -e "  4. Check logs in logs/error.log"

if [ "$ENV" = "production" ]; then
    echo -e "\n${RED}IMPORTANT:${NC}"
    echo -e "  - Keep .env file secure and never commit to version control"
    echo -e "  - Ensure HTTPS is enabled (check BASE_URL)"
    echo -e "  - Review security settings in .env.production"
    echo -e "  - Set up regular backups"
    echo -e "  - Monitor logs/error.log for issues"
fi

echo -e "\n${GREEN}Deployment script completed successfully!${NC}"

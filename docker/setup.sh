#!/bin/bash

set -e

echo "Setting up Laravel + Nuxt E-commerce Platform..."

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW} Installing backend dependencies...${NC}"
docker compose exec backend composer install --optimize-autoloader --no-dev

echo -e "${YELLOW} Generating application key...${NC}"
docker compose exec backend php artisan key:generate

echo -e "${YELLOW}Running database migrations...${NC}"
docker compose exec backend php artisan migrate --force

echo -e "${YELLOW} Creating storage symlink...${NC}"
docker compose exec backend php artisan storage:link

echo -e "${YELLOW} Caching configuration...${NC}"
docker compose exec backend php artisan config:cache
docker compose exec backend php artisan route:cache
docker compose exec backend php artisan view:cache

echo -e "${YELLOW} Setting permissions...${NC}"
docker compose exec backend chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo -e "${GREEN} Setup complete!${NC}"
echo ""
echo "Access your application:"
echo "  Frontend: http://localhost"
echo "  Backend API: http://localhost/api"
echo ""
EOF

chmod +x docker/setup.sh

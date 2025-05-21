#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Starting setup process...${NC}"

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Please run as root or with sudo${NC}"
    exit 1
fi

# Update package lists
echo -e "${YELLOW}Updating package lists...${NC}"
apt-get update

# Install PHP and required extensions
echo -e "${YELLOW}Installing PHP and required extensions...${NC}"
apt-get install -y php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-zip php8.2-gd php8.2-curl php8.2-bcmath php8.2-intl php8.2-pcntl php8.2-exif

# Install MySQL
echo -e "${YELLOW}Installing MySQL...${NC}"
apt-get install -y mysql-server

# Start MySQL service
echo -e "${YELLOW}Starting MySQL service...${NC}"
systemctl start mysql
systemctl enable mysql

# Configure MySQL
echo -e "${YELLOW}Configuring MySQL...${NC}"
mysql -e "CREATE DATABASE IF NOT EXISTS eshop;"
mysql -e "CREATE USER IF NOT EXISTS 'eshop'@'localhost' IDENTIFIED BY 'eshoppassword';"
mysql -e "GRANT ALL PRIVILEGES ON eshop.* TO 'eshop'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Install Composer
echo -e "${YELLOW}Installing Composer...${NC}"
if [ ! -f /usr/local/bin/composer ]; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# Install project dependencies
echo -e "${YELLOW}Installing project dependencies...${NC}"
composer install

# Configure environment
echo -e "${YELLOW}Configuring environment...${NC}"
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Update database configuration in .env
sed -i 's|DATABASE_URL=.*|DATABASE_URL="mysql://eshop:eshoppassword@127.0.0.1:3306/eshop?serverVersion=8.0&charset=utf8mb4"|' .env

# Set proper permissions
echo -e "${YELLOW}Setting proper permissions...${NC}"
chown -R www-data:www-data var/
chmod -R 777 var/

# Run database migrations
echo -e "${YELLOW}Running database migrations...${NC}"
php bin/console doctrine:migrations:migrate --no-interaction

# Create test products
echo -e "${YELLOW}Creating test products...${NC}"
php bin/console app:create-clothes-products

# Create a start script
echo -e "${YELLOW}Creating start script...${NC}"
cat > start.sh << 'EOF'
#!/bin/bash
php -S localhost:8000 -t public
EOF
chmod +x start.sh

echo -e "${GREEN}Setup completed successfully!${NC}"
echo -e "${YELLOW}To start the application, run:${NC}"
echo -e "${GREEN}./start.sh${NC}"
echo -e "${YELLOW}Then open your browser and go to:${NC}"
echo -e "${GREEN}http://localhost:8000${NC}" 
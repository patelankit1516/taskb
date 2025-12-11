#!/bin/bash

# Discount Demo Application - Automated Setup Script
# This script sets up the complete demo application with Laravel

set -e

echo "=========================================="
echo "🎁 Discount Demo Setup Script"
echo "=========================================="
echo ""

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo "❌ Error: composer.json not found!"
    echo "Please run this script from the demo-app directory"
    exit 1
fi

echo "📦 Step 1: Installing Composer dependencies..."
composer install --no-interaction --prefer-dist

echo ""
echo "🔑 Step 2: Setting up environment..."
if [ ! -f ".env" ]; then
    cp .env.example .env
    echo "✅ Created .env file"
else
    echo "⚠️  .env already exists, skipping..."
fi

echo ""
echo "🔐 Step 3: Generating application key..."
php artisan key:generate --force

echo ""
echo "📝 Step 4: Database Configuration"
echo "-----------------------------------"
read -p "Enter database name [discount_demo]: " DB_NAME
DB_NAME=${DB_NAME:-discount_demo}

read -p "Enter database username [root]: " DB_USER
DB_USER=${DB_USER:-root}

read -sp "Enter database password: " DB_PASS
echo ""

# Update .env file
sed -i "s/DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env
sed -i "s/DB_USERNAME=.*/DB_USERNAME=$DB_USER/" .env
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASS/" .env

echo "✅ Database configuration updated"

echo ""
echo "🗄️  Step 5: Creating database..."
mysql -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || {
    echo "⚠️  Could not create database automatically. Please create it manually:"
    echo "   mysql -u $DB_USER -p -e \"CREATE DATABASE $DB_NAME;\""
}

echo ""
echo "📚 Step 6: Publishing package assets..."
php artisan vendor:publish --tag=discounts-migrations --force
php artisan vendor:publish --tag=discounts-config --force

echo ""
echo "🏗️  Step 7: Running migrations..."
php artisan migrate --force

echo ""
echo "🎨 Step 8: Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo ""
echo "=========================================="
echo "✅ Setup Complete!"
echo "=========================================="
echo ""
echo "🚀 To start the server, run:"
echo "   php artisan serve"
echo ""
echo "Then visit: http://localhost:8000"
echo ""
echo "📖 Quick Start:"
echo "   1. Click 'Create Sample Discounts'"
echo "   2. Click 'Create Sample Users'"
echo "   3. Assign discounts to users"
echo "   4. Test discount calculations"
echo ""
echo "Happy Testing! 🎉"

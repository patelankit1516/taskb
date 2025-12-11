# 🎁 Discount Demo Application - Complete Setup Instructions

## Overview

This is a **fully functional Laravel application** demonstrating the TaskB User Discounts Package with a beautiful web interface for testing all features.

---

## 🚀 Quick Start (3 Commands)

```bash
cd demo-app
composer install
php artisan migrate
php artisan serve
```

Visit: **http://localhost:8000**

---

## 📋 Detailed Setup

### Step 1: Install Laravel Dependencies

```bash
cd /var/www/html/laravel/taskb/demo-app
composer install
```

This will:
- Install Laravel framework
- Install the User Discounts package from parent directory
- Setup PSR-4 autoloading

### Step 2: Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` for your database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=discount_demo
DB_USERNAME=root
DB_PASSWORD=your_password_here
```

### Step 3: Create Database

```bash
mysql -u root -p -e "CREATE DATABASE discount_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Or using MySQL Workbench / phpMyAdmin.

### Step 4: Publish Package Assets

```bash
php artisan vendor:publish --tag=discounts-migrations
php artisan vendor:publish --tag=discounts-config
```

This publishes:
- `database/migrations/` - 3 migration files
- `config/discounts.php` - Package configuration

### Step 5: Run Migrations

```bash
php artisan migrate
```

Creates tables:
- `discounts` - Discount definitions
- `user_discounts` - User-discount assignments
- `discount_audits` - Audit trail
- `users` - Laravel users table

### Step 6: Start Development Server

```bash
php artisan serve
```

Access at: **http://localhost:8000**

---

## 🎯 How to Use the Demo

### 1. Initial Setup (One-Time)

When you first visit the dashboard:

1. Click **"📊 Create Sample Discounts"**
   - Creates 5 different discount types
   - Percentage and fixed discounts
   - Various usage limits

2. Click **"👥 Create Sample Users"**
   - Creates 5 test users
   - Default password: `password`

### 2. Test Assignment

1. Select a user from dropdown
2. Select a discount from dropdown
3. Click **"✅ Assign Discount"**
4. Success message confirms assignment

### 3. Test Calculation (Preview Mode)

1. Select a user
2. Enter cart amount (e.g., $100)
3. Click **"💡 Preview (Calculate Only)"**
4. See results WITHOUT using the discount
5. Usage counter is NOT incremented

### 4. Test Application (Real Usage)

1. Select a user
2. Enter cart amount
3. Click **"✅ Apply Discount (Use It)"**
4. See results AND increment usage counter
5. Discount is actually used

### 5. View Audit Trail

1. Click **"📜 Audit Trail"** card
2. See complete history of:
   - Discount assignments
   - Discount applications
   - Discount revocations
3. Filter by user if needed

### 6. Check User Status

1. Click on any user card
2. View:
   - ✅ Eligible discounts (can use now)
   - 📊 All assigned discounts
   - 📜 Recent activity
3. See usage counts and limits

---

## 🏗️ Architecture Demonstrated

### Design Patterns
- **Repository Pattern** - `DiscountRepository` for data access
- **Strategy Pattern** - 3 stacking strategies (sequential, best, all)
- **Service Layer** - `DiscountService` orchestrates logic
- **DTO Pattern** - `DiscountApplicationResult` for immutable results
- **Observer Pattern** - Events (Assigned, Applied, Revoked)
- **Facade Pattern** - Simple API access

### SOLID Principles
- ✅ Single Responsibility
- ✅ Open/Closed
- ✅ Liskov Substitution
- ✅ Interface Segregation
- ✅ Dependency Inversion

### Code Quality
- ✅ PHP 8.1+ strict types
- ✅ Readonly properties
- ✅ Constructor property promotion
- ✅ Database transactions
- ✅ Pessimistic locking
- ✅ Comprehensive error handling

---

## 📊 Testing Scenarios

### Scenario 1: Sequential Stacking (Default Config)

```
User: John Doe
Cart: $100.00
Discounts Assigned: 10%, 20%

Result:
- 10% off $100 = $90
- 20% off $90 = $72
- Total Saved: $28 (28%)
```

### Scenario 2: Usage Limit Testing

```
1. Assign discount with max_usage_per_user = 1
2. Apply once (success)
3. Apply again (error: usage limit reached)
4. Check audit trail (shows both attempts)
```

### Scenario 3: Expired Discount

```
1. View discount with expired date
2. Assign to user
3. Try to calculate/apply
4. Discount not included (filtered out)
```

### Scenario 4: Multiple Strategies

Edit `config/discounts.php`:

```php
// Try different strategies:
'stacking_strategy' => 'sequential',  // Compound effect
'stacking_strategy' => 'best',        // Only best discount
'stacking_strategy' => 'all',         // Sum all discounts
```

Then test with same discounts, see different results!

---

## 🎨 UI Features

### Dashboard (`/discount-demo`)
- Available discounts list with status badges
- Registered users list
- Assign discount form
- Apply/Calculate discount interface
- Real-time AJAX results
- Quick action cards

### Audit Trail (`/discount-demo/audits`)
- Paginated audit log
- Filterable by user
- Shows: action, timestamp, amount, performer
- Color-coded action types

### User Status (`/discount-demo/user/{id}`)
- Eligible discounts (green cards)
- All assigned discounts (usage tracking)
- Recent activity timeline
- Usage statistics

---

## 📁 Project Structure

```
demo-app/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── DiscountDemoController.php    # Main controller
│   └── Models/
│       └── User.php                           # Laravel user model
├── config/
│   ├── app.php                                # Laravel config
│   └── discounts.php                          # Package config (published)
├── database/
│   └── migrations/                            # Discount tables (published)
├── resources/
│   └── views/
│       └── discount-demo/
│           ├── index.blade.php                # Dashboard
│           ├── audits.blade.php               # Audit trail
│           └── user-status.blade.php          # User details
├── routes/
│   └── web.php                                # All routes
├── .env.example                               # Environment template
├── composer.json                              # Dependencies
├── README.md                                  # This file
├── SETUP_GUIDE.md                             # Detailed instructions
└── setup.sh                                   # Automated setup script
```

---

## 🔧 Configuration

### Change Stacking Strategy

Edit `config/discounts.php`:

```php
'stacking_strategy' => 'sequential',  // Apply one after another (default)
// OR
'stacking_strategy' => 'best',        // Only best discount
// OR
'stacking_strategy' => 'all',         // Sum all discounts
```

### Change Maximum Cap

```php
'max_percentage_cap' => 90,  // Max 90% total discount (default)
```

### Enable/Disable Audit

```php
'enable_audit' => true,  // Log all operations (default)
```

### Queue Events

```php
'queue_events' => false,  // Process events immediately (default)
```

---

## 🐛 Troubleshooting

### "Class not found" Error

```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### "SQLSTATE[HY000] [1045]" Database Error

- Check `.env` database credentials
- Ensure MySQL is running: `sudo systemctl status mysql`
- Create database: `CREATE DATABASE discount_demo;`

### "SQLSTATE[42S02]: Base table or view not found"

```bash
php artisan migrate
```

### Routes Not Working

```bash
php artisan route:clear
php artisan route:cache
```

### Package Not Found

Check `composer.json`:

```json
"repositories": [
    {
        "type": "path",
        "url": "../"
    }
],
"require": {
    "taskb/user-discounts": "*"
}
```

Then:
```bash
composer update taskb/user-discounts
```

---

## 🧪 API Endpoints (For Testing)

All routes are in `routes/web.php`:

```
GET  /discount-demo                    Dashboard
POST /discount-demo/create-samples     Create sample discounts
POST /discount-demo/create-users       Create sample users
POST /discount-demo/assign             Assign discount to user
POST /discount-demo/calculate          Calculate (preview) discounts
POST /discount-demo/apply              Apply (use) discounts
POST /discount-demo/revoke             Revoke discount from user
GET  /discount-demo/eligible           Get eligible discounts for user
GET  /discount-demo/audits             View audit trail
GET  /discount-demo/user/{id}          View user status
```

---

## 📞 Support

For help:
- Package documentation: `../DOCUMENTATION.md`
- Implementation summary: `../IMPLEMENTATION_SUMMARY.md`
- Architecture diagram: `../ARCHITECTURE.md`
- Quick start guide: `../QUICK_START.md`

---

## ✅ Success Checklist

- [ ] Composer install completed
- [ ] .env file configured
- [ ] Database created
- [ ] Migrations run successfully
- [ ] Server started (php artisan serve)
- [ ] Dashboard accessible at http://localhost:8000
- [ ] Sample discounts created
- [ ] Sample users created
- [ ] Discount assigned successfully
- [ ] Calculation tested (preview mode)
- [ ] Application tested (real usage)
- [ ] Audit trail viewed
- [ ] User status page viewed

---

## 🎉 You're Ready!

The demo is now fully functional. You can:

✅ Create and manage discounts
✅ Assign discounts to users
✅ Calculate discounts (preview)
✅ Apply discounts (actual usage)
✅ View complete audit trail
✅ Check user discount status
✅ Test all design patterns
✅ Verify SOLID principles
✅ Demonstrate enterprise architecture

**Perfect for interviews, demos, and learning! 🚀**

---

**Built with ❤️ for TaskB Interview Assignment**

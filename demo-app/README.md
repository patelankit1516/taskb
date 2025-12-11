# Discount Demo Application - Setup Guide

## 🚀 Quick Start Guide

This is a complete Laravel demo application integrated with the TaskB User Discounts Package.

## Prerequisites

- PHP 8.1 or higher
- Composer
- MySQL or PostgreSQL
- Node.js & NPM (optional, for Vite)

## Installation Steps

### 1. Install Dependencies

```bash
cd demo-app
composer install
```

### 2. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configure Database

Edit `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=discount_demo
DB_USERNAME=root
DB_PASSWORD=your_password
```

Create the database:

```bash
mysql -u root -p -e "CREATE DATABASE discount_demo;"
```

### 4. Publish Package Assets

```bash
php artisan vendor:publish --tag=discounts-migrations
php artisan vendor:publish --tag=discounts-config
```

### 5. Run Migrations

```bash
php artisan migrate
```

### 6. Start the Server

```bash
php artisan serve
```

Visit: **http://localhost:8000**

---

## 🎯 What You Can Test

### 1. **Create Sample Data**
- Click "Create Sample Discounts" to generate 5 different discount types
- Click "Create Sample Users" to generate 5 test users

### 2. **Assign Discounts**
- Select a user and discount from dropdowns
- Click "Assign Discount"
- System uses Repository Pattern for data access

### 3. **Calculate Discounts (Preview)**
- Select user and enter cart amount
- Click "Preview" to see calculation WITHOUT using the discount
- Usage counter is NOT incremented
- Tests the Strategy Pattern implementation

### 4. **Apply Discounts (Use Them)**
- Select user and enter cart amount
- Click "Apply Discount" to actually USE the discount
- Usage counter IS incremented
- Demonstrates transaction safety and concurrency control

### 5. **View Audit Trail**
- Click "Audit Trail" to see complete operation history
- Shows: assigned, applied, revoked actions
- Demonstrates Observer Pattern with events

### 6. **Check User Status**
- Click on any user card
- See all assigned discounts
- See eligible discounts (can use now)
- View recent activity

---

## 🏗️ Architecture Features Demonstrated

### Design Patterns Used:
- ✅ **Repository Pattern** - Data access abstraction
- ✅ **Strategy Pattern** - 3 different stacking strategies
- ✅ **Service Layer** - Business logic orchestration
- ✅ **DTO Pattern** - Immutable result objects
- ✅ **Observer Pattern** - Event system
- ✅ **Facade Pattern** - Simplified API access
- ✅ **Dependency Injection** - Constructor injection throughout

### SOLID Principles:
- ✅ **Single Responsibility** - Each class has one job
- ✅ **Open/Closed** - Extendable strategies
- ✅ **Liskov Substitution** - Interface implementations
- ✅ **Interface Segregation** - Small, focused interfaces
- ✅ **Dependency Inversion** - Depend on abstractions

### Code Quality:
- ✅ **Concurrency Safe** - Database transactions + pessimistic locking
- ✅ **Type Safety** - PHP 8.1+ strict types, readonly properties
- ✅ **Exception Handling** - Custom exceptions with clear messages
- ✅ **Audit Trail** - Complete operation logging
- ✅ **Configuration** - Centralized, flexible config
- ✅ **Testing Ready** - Built with testability in mind

---

## 📊 Testing Scenarios

### Scenario 1: Sequential Stacking (Default)
1. Assign multiple percentage discounts to a user
2. Set cart amount to $100
3. Click "Apply Discount"
4. See discounts applied sequentially (compound effect)

**Example:**
- 10% off: $100 → $90
- 20% off on $90: $90 → $72
- **Total saved: $28 (28%)**

### Scenario 2: Usage Cap Testing
1. Assign a discount with max_usage_per_user = 1
2. Apply it once successfully
3. Try to apply again
4. Should fail with usage limit error

### Scenario 3: Expired Discount
1. Create discount with past expiry date
2. Assign to user
3. Try to apply
4. Should not appear in eligible discounts

### Scenario 4: Revocation
1. Assign discount to user
2. View user status (should show as active)
3. Revoke the discount
4. Try to apply (should fail)
5. Check audit trail (shows revocation)

### Scenario 5: Concurrent Safety
1. Open two browser tabs
2. Both apply same discount simultaneously
3. System handles with DB transactions
4. Only one succeeds, other gets proper error

---

## 🎨 UI Features

- **Responsive Design** - Works on mobile, tablet, desktop
- **Tailwind CSS** - Modern, clean styling
- **Real-time Updates** - AJAX-powered calculations
- **Visual Feedback** - Success/error messages
- **Interactive Cards** - Hover effects, animations
- **Color Coding** - Green (active), Red (inactive), Blue (info)

---

## 📁 File Structure

```
demo-app/
├── app/
│   └── Http/
│       └── Controllers/
│           └── DiscountDemoController.php    # Main controller
├── routes/
│   └── web.php                               # All demo routes
├── resources/
│   └── views/
│       └── discount-demo/
│           ├── index.blade.php               # Main dashboard
│           ├── audits.blade.php              # Audit trail view
│           └── user-status.blade.php         # User detail view
├── composer.json                             # Package dependencies
└── .env.example                              # Environment template
```

---

## 🔧 Configuration Options

Edit `config/discounts.php`:

```php
return [
    // Stacking strategy: 'sequential', 'best', or 'all'
    'stacking_strategy' => 'sequential',
    
    // Maximum percentage discount cap (0-100)
    'max_percentage_cap' => 90,
    
    // Rounding mode for calculations
    'rounding_mode' => PHP_ROUND_HALF_UP,
    
    // Enable audit logging
    'enable_audit' => true,
    
    // Queue discount events
    'queue_events' => false,
];
```

**Try different strategies:**
- `sequential` - Apply one after another (compound)
- `best` - Only apply the best single discount
- `all` - Sum all discounts on original amount

---

## 🐛 Troubleshooting

### "Class not found" errors
```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### "Table doesn't exist"
```bash
php artisan migrate:fresh
```

### "Package not found"
Make sure parent package is in `composer.json` repositories:
```json
"repositories": [
    {
        "type": "path",
        "url": "../"
    }
]
```

### Database connection errors
- Check `.env` database credentials
- Ensure MySQL/PostgreSQL is running
- Verify database exists

---

## 🎓 Learning Points

This demo showcases:

1. **Enterprise Laravel Development**
   - Package development
   - Service provider usage
   - Dependency injection
   - Event handling

2. **Design Patterns in Practice**
   - Not just theory - real implementations
   - Clean, maintainable code
   - Testable architecture

3. **Database Best Practices**
   - Proper migrations
   - Foreign keys & indexes
   - Transaction safety
   - Audit logging

4. **Modern PHP**
   - PHP 8.1+ features
   - Strict types
   - Named arguments
   - Constructor promotion

---

## 📞 Support

For issues or questions:
- Check package documentation: `../DOCUMENTATION.md`
- View implementation summary: `../IMPLEMENTATION_SUMMARY.md`
- See architecture diagram: `../ARCHITECTURE.md`

---

## ✅ Checklist

- [ ] Composer install completed
- [ ] .env configured
- [ ] Database created
- [ ] Migrations run
- [ ] Server started
- [ ] Sample data created
- [ ] Discounts assigned
- [ ] Calculations tested
- [ ] Audit trail viewed
- [ ] User status checked

---

**Happy Testing! 🎉**

This demo proves the package follows senior-level development practices with proper architecture, design patterns, and code quality.

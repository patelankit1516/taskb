# User Discounts Laravel Package

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-%5E8.1-blue)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-10%7C11-red)](https://laravel.com)

A professional, reusable Laravel package for managing user-level discounts with deterministic stacking, comprehensive audit trails, and full concurrency safety.

## Features

✨ **Core Functionality**
- Assign and revoke discounts to users
- Check discount eligibility  
- Apply discounts with configurable stacking strategies
- Full audit trail of all discount operations

🔒 **Production-Ready**
- Concurrency-safe using pessimistic locking
- Idempotent discount application
- Prevents double-increment of usage counters
- Database transactions for atomicity

🎯 **Flexible & Extensible**
- Multiple discount types (percentage, fixed)
- Three stacking strategies (sequential, best, all)
- Configurable percentage caps and rounding
- Usage limits per user and globally
- Event-driven architecture

## Architecture & Design Patterns

This package follows senior-level best practices (10+ years experience):

- **SOLID Principles**: Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion
- **Repository Pattern**: Clean separation of data access logic
- **Strategy Pattern**: Pluggable discount stacking algorithms
- **Observer Pattern**: Event-driven architecture for extensibility
- **Data Transfer Objects (DTOs)**: Immutable result objects
- **Dependency Injection**: Fully IoC container compatible
- **PSR-4 Autoloading**: Standard PHP package structure

## Installation

```bash
composer require taskb/user-discounts
php artisan vendor:publish --tag=discounts-config
php artisan vendor:publish --tag=discounts-migrations
php artisan migrate
```

## Quick Start

```php
use TaskB\UserDiscounts\Services\DiscountService;
use TaskB\UserDiscounts\Models\Discount;

$discountService = app(DiscountService::class);

// Create a discount
$discount = Discount::create([
    'name' => '20% Off',
    'code' => 'SAVE20',
    'type' => Discount::TYPE_PERCENTAGE,
    'value' => 20.00,
    'is_active' => true,
    'max_usage_per_user' => 3,
]);

// Assign to user
$discountService->assignDiscount(userId: 1, discountId: $discount->id);

// Apply discounts
$result = $discountService->applyDiscounts(userId: 1, amount: 100.00);

echo "Original: $" . $result->originalAmount;  // 100.00
echo "Discount: $" . $result->discountAmount;  // 20.00
echo "Final: $" . $result->finalAmount;        // 80.00
```

## Demo Application

A full-featured demo application is included in the `demo-app/` directory with:

- Interactive web UI with Tailwind CSS
- Complete CRUD operations for discounts and users
- Real-time discount calculation and application
- Audit trail viewer with user names
- One-time use enforcement examples
- Revoke functionality

### Running the Demo

```bash
cd demo-app
composer install
cp .env.example .env
# Configure your database in .env
php artisan migrate
php artisan serve
```

Visit http://127.0.0.1:8000/discount-demo to see the package in action!

## Testing

```bash
composer test
```

## Key Features Implemented

✅ **Usage Enforcement**: One-time use per user (configurable)  
✅ **Duplicate Prevention**: Cannot assign same discount twice to a user  
✅ **Audit Trail**: Complete history with user names and actions  
✅ **Soft Deletes**: Discounts can be restored after deletion  
✅ **Revoke Support**: Remove discount access from users  
✅ **Concurrency Safe**: Pessimistic locking prevents race conditions  

**Built with ❤️ following SOLID principles and enterprise-level best practices.**
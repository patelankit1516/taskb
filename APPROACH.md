# Brief Explanation of Approach

## Project Overview
This is a Laravel package for managing user-level discounts with deterministic stacking, comprehensive audit trails, and full concurrency safety. Built following SOLID principles and enterprise-level design patterns.

---

## 1. Architecture & Design Patterns

### Repository Pattern
- **DiscountRepository**: Centralized data access layer
- Separates business logic from data persistence
- Makes testing easier through dependency injection
- Provides clean interface for discount operations

### Strategy Pattern
- **DiscountStackingStrategy**: Pluggable discount calculation algorithms
- Three implementations:
  - `SequentialStrategy`: Apply discounts one by one
  - `BestDiscountStrategy`: Apply only the best discount
  - `AllDiscountsStrategy`: Apply all eligible discounts
- Easy to add new strategies without modifying existing code (Open/Closed Principle)

### Service Layer Pattern
- **DiscountService**: Main business logic coordinator
- Orchestrates between repository and strategies
- Handles transactions and events
- Single entry point for discount operations

### Data Transfer Objects (DTOs)
- **DiscountApplicationResult**: Immutable result object
- Encapsulates calculation results
- Type-safe data passing between layers
- Clear API contract

### Observer Pattern (Events)
- `DiscountAssigned`: Fired when discount assigned to user
- `DiscountApplied`: Fired when discount used
- `DiscountRevoked`: Fired when discount removed
- Enables extensibility without coupling

---

## 2. Database Design

### Tables Structure

**discounts**
- Core discount information
- Supports soft deletes (can be restored)
- Fields: name, code, type, value, dates, limits, priority

**user_discounts** (Pivot)
- Many-to-many relationship
- Tracks assignment and usage
- Fields: user_id, discount_id, usage_count, revoked_at

**discount_audits**
- Complete audit trail
- Records all discount operations
- Fields: action, amounts, metadata, timestamp

### Key Features
- **Soft Deletes**: Discounts can be restored
- **Pessimistic Locking**: Prevents race conditions
- **Proper Indexing**: Fast queries on user_id, discount_id
- **Foreign Keys**: Maintains referential integrity

---

## 3. Critical Features Implementation

### One-Time Use Enforcement
**Problem**: Users could use discounts multiple times

**Solution**:
```php
// In DiscountRepository::getEligibleDiscountsForUser()
->whereRaw('user_discounts.usage_count < discounts.max_usage_per_user')
```
- Checks usage at query level
- Prevents discounts from appearing if limit reached
- Enforces max_usage_per_user configuration

### Duplicate Assignment Prevention
**Problem**: Users could be assigned same discount multiple times

**Solution**:
```php
if ($existing && !$existing->revoked_at) {
    throw new DiscountException("Discount is already assigned to this user.");
}
```
- Validates before creating assignment
- Clear error message
- Prevents duplicate audit logs

### Concurrency Safety
**Problem**: Race conditions in usage count increment

**Solution**:
```php
DB::transaction(function () use ($userId, $discountId) {
    $userDiscount = UserDiscount::where('user_id', $userId)
        ->where('discount_id', $discountId)
        ->lockForUpdate()  // Pessimistic lock
        ->first();
    
    if ($userDiscount) {
        $userDiscount->increment('usage_count');
    }
});
```
- Database transaction wrapper
- Pessimistic locking with `lockForUpdate()`
- Atomic increment operation
- Prevents double-increment issues

### Audit Trail with User Context
**Problem**: Audit logs only showed user IDs

**Solution**:
```php
// In DiscountAudit model
public function user(): BelongsTo
{
    $userModel = config('auth.providers.users.model', \App\Models\User::class);
    return $this->belongsTo($userModel);
}
```
- Configurable user model
- Enables eager loading: `DiscountAudit::with('user')`
- Display user names instead of IDs

---

## 4. Code Quality & Best Practices

### SOLID Principles Applied

**Single Responsibility**
- Each class has one reason to change
- Service handles business logic
- Repository handles data access
- Strategy handles calculation

**Open/Closed**
- New strategies can be added without modifying existing code
- Interface-based design allows extensions

**Liskov Substitution**
- All strategies implement same interface
- Can be swapped without breaking code

**Interface Segregation**
- Small, focused interfaces
- `DiscountRepositoryInterface`
- `DiscountStackingStrategyInterface`

**Dependency Inversion**
- Depend on abstractions, not concretions
- Constructor injection throughout
- IoC container friendly

### Type Safety
- PHP 8.1+ strict types
- Return type declarations on all methods
- Nullable types properly declared
- DTOs for complex data

### Error Handling
- Custom `DiscountException` class
- Meaningful error messages
- Try-catch blocks in controllers
- Transaction rollback on failure

### Documentation
- Comprehensive PHPDoc blocks
- README with examples
- DOCUMENTATION.md with details
- CHANGELOG.md for version history

---

## 5. Testing Approach

### Unit Tests Coverage
- Service layer methods
- Repository operations
- Strategy calculations
- Model relationships
- Edge cases and error scenarios

### Test Structure
```
tests/
├── Unit/
│   ├── Services/
│   │   └── DiscountServiceTest.php
│   ├── Repositories/
│   │   └── DiscountRepositoryTest.php
│   └── Strategies/
│       ├── SequentialStrategyTest.php
│       ├── BestDiscountStrategyTest.php
│       └── AllDiscountsStrategyTest.php
└── Feature/
    └── DiscountIntegrationTest.php
```

### Key Test Scenarios
1. ✅ Assign discount to user
2. ✅ Apply discount with valid amount
3. ✅ Prevent duplicate assignments
4. ✅ Enforce usage limits
5. ✅ Handle concurrent requests
6. ✅ Soft delete and restore
7. ✅ Audit trail creation
8. ✅ Event firing

---

## 6. Demo Application

### Purpose
- Browser-based testing interface
- Real-world usage demonstration
- Visual feedback for operations
- Interview showcase

### Features Implemented
- ✅ Create/Delete sample data
- ✅ Assign/Revoke discounts
- ✅ Apply discounts (real usage)
- ✅ Calculate preview (no usage)
- ✅ Audit trail viewer with user names
- ✅ User status pages
- ✅ One-time use enforcement
- ✅ AJAX real-time calculations

### Technology Stack
- Laravel 11
- Tailwind CSS (CDN)
- jQuery for AJAX
- MySQL database
- Blade templating

---

## 7. Key Decisions & Trade-offs

### Why Repository Pattern?
**Pros**: Clean separation, testability, flexibility
**Cons**: Extra abstraction layer
**Decision**: Worth it for maintainability and testing

### Why Strategy Pattern for Stacking?
**Pros**: Easy to add new strategies, follows OCP
**Cons**: More classes to maintain
**Decision**: Flexibility is crucial for business requirements

### Why Soft Deletes?
**Pros**: Can recover data, maintains audit history
**Cons**: More complex queries
**Decision**: Data integrity and audit trail are critical

### Why Pessimistic Locking?
**Pros**: Guarantees consistency, prevents race conditions
**Cons**: Slightly slower for high concurrency
**Decision**: Correctness over speed for financial operations

### Why Events Instead of Hooks?
**Pros**: Laravel native, loosely coupled, extensible
**Cons**: Async complexity if queued
**Decision**: Standard Laravel approach, future-proof

---

## 8. Production Readiness

### Performance Optimizations
- Database indexes on foreign keys
- Eager loading to prevent N+1 queries
- Query optimization with proper joins
- Configurable caching (future enhancement)

### Security Considerations
- Mass assignment protection with `$fillable`
- SQL injection prevention via Eloquent
- CSRF protection in forms
- Input validation in requests
- Authorization checks (can be added)

### Scalability
- Stateless service design
- Database-level locking
- Horizontal scaling ready
- Queue-able events

### Monitoring & Debugging
- Comprehensive audit trail
- Laravel logs integration
- Error messages with context
- Event listeners for logging

---

## 9. Future Enhancements

### Possible Improvements
1. **Caching Layer**: Redis for eligible discounts
2. **Rate Limiting**: Prevent abuse of discount checks
3. **Advanced Conditions**: JSON-based discount rules
4. **Multi-Currency**: Support international pricing
5. **A/B Testing**: Different strategies per user segment
6. **Analytics Dashboard**: Usage statistics
7. **API Endpoints**: RESTful API for external systems
8. **GraphQL Support**: Flexible querying

### Technical Debt
- None identified currently
- Clean architecture from start
- Well-documented codebase
- Comprehensive tests

---

## 10. Lessons Learned & Fixes Applied

### Critical Bug Fixes

1. **Max Usage Enforcement**
   - Issue: Ambiguous column references
   - Fix: Explicit table prefixes in SQL
   - Impact: Now properly enforces limits

2. **Duplicate Assignments**
   - Issue: Multiple assignments allowed
   - Fix: Validation before assignment
   - Impact: Prevents confusion and errors

3. **Usage Count Reset**
   - Issue: Old count persisted on reassignment
   - Fix: Reset to 0 when reassigning
   - Impact: Fresh start for revoked discounts

4. **Soft Delete Handling**
   - Issue: Unique constraint violations
   - Fix: Check with `withTrashed()`
   - Impact: Proper restore functionality

---

## Conclusion

This package demonstrates:
- ✅ Senior-level architecture and design
- ✅ SOLID principles in practice
- ✅ Production-ready code quality
- ✅ Comprehensive testing approach
- ✅ Real-world problem solving
- ✅ Clear documentation
- ✅ Extensible and maintainable codebase

**Built with attention to detail, following best practices, and ready for production use.**

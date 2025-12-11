# Changelog

All notable changes to the User Discounts Package.

## [1.0.1] - 2025-12-11

### Fixed
- **Max Usage Per User Enforcement**: Fixed SQL query in `getEligibleDiscountsForUser()` to properly reference table columns (`user_discounts.usage_count < discounts.max_usage_per_user`)
- **Duplicate Assignment Prevention**: Added exception throw when attempting to assign already-assigned discount
- **Usage Count Reset**: Reset `usage_count` to 0 when reassigning a previously revoked discount
- **Audit User Relationship**: Made user relationship in `DiscountAudit` configurable via auth config

### Added
- User relationship to `DiscountAudit` model for eager loading
- Comprehensive demo application in `demo-app/` directory with full UI
- Better error handling and validation messages

### Improved
- Documentation with important fixes section
- README with demo application instructions
- Code comments and type hints

### Removed
- Unnecessary markdown files (Task.md, START_HERE.md, TASK_COMPLETE.md, etc.)
- Old demo directory (replaced with demo-app)

## [1.0.0] - 2025-12-10

### Added
- Initial release
- Discount assignment and revocation
- Multiple stacking strategies (sequential, best, all)
- Audit trail system
- Event-driven architecture
- Concurrency-safe operations with pessimistic locking
- Comprehensive test suite
- Full documentation

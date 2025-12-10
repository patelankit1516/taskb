Task B — Laravel Package: User Discounts
Goal:
Create a reusable Laravel package for user-level discounts, with deterministic stacking and a full
automated test suite.
Scope:
- Package installable via Composer (PSR-4, versioned).
- Migrations: discounts, user_discounts, discount_audits.
- Functions: assign, revoke, eligibleFor, apply.
- Config: stacking order, max percentage cap, rounding.
- Events: DiscountAssigned, DiscountRevoked, DiscountApplied.
Rules:
- Expired/inactive discounts ignored.
- Per-user usage cap enforced.
- Application deterministic and idempotent.

- Concurrent apply must not double-increment usage.
Acceptance:
- Assign → eligible → apply works correctly with audits.
- Expired/inactive excluded.
- Usage caps enforced.
- Stacking and rounding correct.
- Revoked discounts not applied.
- Concurrency safe.
Unit Test (Required): At least one unit test must validate discount application or usage cap logic.
Feature tests are optional.
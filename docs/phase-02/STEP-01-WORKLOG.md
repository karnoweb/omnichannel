# STEP 01 — implementation evidence

Status: **Verified** (SQLite + mock scope). MySQL/PostgreSQL not executed per owner policy.

## Implemented

- Publication claims include schedule snapshot and due-time checks.
- Unknown content updates retain operation ownership.
- Legacy idempotency validates branch, connection, channel, and schedule.
- History retention migrations repaired for SQLite upgrades.
- Media resolver IPv6 pinning, streaming byte limits, special-use IPv4/IPv6 rejection.
- `branch_mode=inherit_global` documented as deprecated (strict only).

## Verification

- Package suite: **79 tests, 8911 assertions** (PHP 8.4.22, PHPUnit 13.4.7, excluding optional `@group redis`).
- Controlled transfer limitations: Laravel `Http::fake()` does not exercise the cURL handler path; rejection and policy tests cover security gates.

## Remaining

- Live cURL integration server tests: **Pending Verification** (environment).
- MySQL/PostgreSQL upgrade matrix: **Blocked by Environment** (excluded from this iteration).
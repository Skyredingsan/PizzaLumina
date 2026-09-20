# PizzaLumina Agent Guide

## Quality checks

Run `make quality-dr` from the repository root. It executes formatting, PHPStan, Rector, migrations and the full test suite.

## Admin API

Admin endpoints require a JWT and the `role:admin` middleware. User roles are validated with `UserRole`.

## Localization

API locale is selected by `Accept-Language` and limited to `ru` and `en`. Keep user-facing messages in language files.

## Admin API safety

Pagination uses `AdminUserListRequest` and `AdminUserListInput`, capped at 100. An administrator cannot change their own role or demote the last administrator; both return HTTP 409.

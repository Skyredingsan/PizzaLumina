# PizzaLumina Agent Guide

## Project overview

PizzaLumina is a Laravel API application running in Docker.

The application source is in `src/` and is organized by bounded modules:

- `app/Modules/User` — authentication, users, roles, admin user API;
- `app/Modules/Product` — products, repositories, caching;
- `app/Modules/Cart` — carts and cart limits;
- `app/Modules/Order` — orders, statuses and delivery;
- `app/Modules/Report` — asynchronous reports, RabbitMQ, MinIO and scheduler;
- `app/Shared` — reusable value objects and casts;
- `database` — migrations, factories and seeders;
- `tests/Feature/Api` — HTTP/API feature tests;
- `tests/Unit` — isolated unit tests.

Keep business logic inside the relevant module. Controllers should remain thin and use FormRequests, DTOs, services and resources where appropriate.

## Development commands

Run commands from the repository root unless noted otherwise.

```bash
docker compose up -d
docker compose ps
docker compose logs --tail=100 php
make quality-dr
```

`make quality-dr` runs PHP CS Fixer, PHPStan, Rector, test database migrations and the complete PHPUnit suite.

Useful targeted commands:

```bash
docker compose exec php php artisan test --filter=TestName
docker compose exec php vendor/bin/phpstan analyse --no-progress
docker compose exec php vendor/bin/rector process --dry-run
docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff
```

If dependencies are missing in a development container, rebuild the image and recreate the services:

```bash
docker compose build php queue-worker queue-completed-worker scheduler
docker compose up -d --force-recreate php queue-worker queue-completed-worker scheduler
```

## API and security rules

- Protected endpoints require JWT authentication.
- Admin endpoints require both `jwt.auth` and `role:admin` middleware.
- User roles must be validated through the `UserRole` enum.
- Never return password hashes, JWTs, remember tokens or other secrets in resources.
- Admin pagination uses `AdminUserListRequest` and `AdminUserListInput`; `per_page` must remain between 1 and 100.
- An administrator cannot change their own role or demote the last administrator; both cases return HTTP 409.
- Unknown route model bindings must return HTTP 404.
- Validate all request input through FormRequest classes.

## Localization

API locale is selected from `Accept-Language` and limited to `ru` and `en` using the locale middleware. User-facing API messages belong in language files and must not be hardcoded in controllers, middleware or exceptions.

## Events and asynchronous processing

- Registration events must be dispatched after a successful database commit.
- Queued listeners and jobs must be idempotent.
- Report generation uses RabbitMQ queues and MinIO storage.
- Do not load thousands of database rows into memory at once; use chunking/cursors and streams.

## Docker and configuration

- PostgreSQL, Redis, RabbitMQ and MinIO run as Compose services.
- Keep credentials in environment variables or local `.env` files; never commit secrets.
- Do not add tokens, passwords or machine-specific absolute paths to this file or repository configuration.
- Validate both development and production Compose files after changes:

```bash
docker compose config --quiet
docker compose -f docker-compose.yml -f docker-compose.prod.yml config --quiet
```

## Change workflow

1. Inspect the existing module and tests before editing.
2. Make the smallest architecture-consistent change.
3. Add or update feature tests for API behavior and authorization.
4. Run targeted tests, then `make quality-dr`.
5. Review `git diff`, `git diff --check` and `git status` before committing.

## MCP and agent tooling

The repository contains only the token-free example at `docs/mcp.example.toml`. Configure MCP servers in the user-level Codex or Claude configuration, never in committed project files. Required environment variables are `CONTEXT7_API_KEY` and `FIRECRAWL_API_KEY`. Restart the agent after changing MCP configuration and verify both servers are connected.

Context Hub generator and external skills must be installed in the agent environment; do not vendor their caches or credentials into this repository.

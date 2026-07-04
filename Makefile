.PHONY: up down build shell logs restart composer-install artisan migrate test test-db-create test-db-drop test-db-reset fresh clean cs-check cs-fix phpstan phpstan-baseline rector rector-dry ci-quality

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build --no-cache

shell:
	docker compose exec php bash

logs:
	docker compose logs -f

restart:
	docker compose restart

composer-install:
	docker compose exec php composer install

artisan:
	docker compose exec php php artisan $(cmd)

migrate:
	docker compose exec php php artisan migrate


test: test-db-reset
	docker compose exec php php artisan test

test-db-reset:
	docker compose exec postgres psql -U laravel -d postgres -c "DROP DATABASE IF EXISTS pizzalumina_test;"
	docker compose exec postgres psql -U laravel -d postgres -c "CREATE DATABASE pizzalumina_test;"
	docker compose exec -e DB_DATABASE=pizzalumina_test php php artisan migrate --database=pgsql

fresh: clean build up
	docker compose exec php php artisan migrate:fresh --seed

clean:
	docker compose down -v
	rm -rf src/vendor src/bootstrap/cache/*

cs-check:
	docker compose exec php vendor/bin/pint --test

cs-fix:
	docker compose exec php vendor/bin/pint

phpstan:
	docker compose exec php vendor/bin/phpstan analyse --no-progress

phpstan-baseline:
	docker compose exec php vendor/bin/phpstan analyse --generate-baseline phpstan-baseline.neon

rector:
	docker compose exec php vendor/bin/rector process

rector-dry:
	docker compose exec php vendor/bin/rector process --dry-run

ci-quality: cs-check phpstan rector-dry test
	@echo "✓ Все проверки пройдены"
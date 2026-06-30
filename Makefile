.PHONY: up down build shell logs restart composer-install artisan migrate test test-db-create test-db-drop test-db-reset fresh clean

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

test-db-reset: test-db-create
	docker compose exec postgres psql -U laravel -d postgres -c "DROP DATABASE IF EXISTS pizzalumina_test;"
	docker compose exec postgres psql -U laravel -d postgres -c "CREATE DATABASE pizzalumina_test;"
	docker compose exec php php artisan migrate --database=pgsql --env=testing

fresh: clean build up
	docker compose exec php php artisan migrate:fresh --seed

clean:
	docker compose down -v
	rm -rf src/vendor src/bootstrap/cache/*
.PHONY: up down build shell logs restart composer-install artisan migrate test

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

test:
	docker compose exec php php artisan test

clean:
	docker compose down -v
	rm -rf src/vendor src/bootstrap/cache/*

.PHONY: up down sh test stan

up:
	docker compose up -d

down:
	docker compose down

sh:
	docker compose exec php bash

test:
	docker compose exec php vendor/bin/phpunit

stan:
	docker compose exec php vendor/bin/phpstan analyse

cs:
	docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix:
	docker compose exec php vendor/bin/php-cs-fixer fix
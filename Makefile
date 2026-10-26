.PHONY: build setup install test lint lint-fix qa shell

build:
	docker compose build

setup:
	docker compose run --rm app composer install --no-interaction

install: build setup

lint:
	docker compose run --rm app sh -lc 'find src -type f -name "*.php" -print0 | xargs -0 -n1 -P 4 php -l'

test:
	docker compose run --rm app ./vendor/bin/phpunit

qa: lint test

shell:
	docker compose run --rm app sh

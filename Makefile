DOCKER_COMPOSE?=docker compose
DOCKER_RUN?=$(DOCKER_COMPOSE) run --rm --no-deps $(GLOBAL_ENVS)
PHP?=php

.PHONY: all
all: build

.PHONY: cs
cs:
	vendor/bin/php-cs-fixer fix

.PHONY: build
build: tests clean cs
	composer install --no-dev
	box compile
	composer install

.PHONY: clean
clean:
	rm -f build/*.phar

.PHONY: tests
tests:
	vendor/bin/simple-phpunit

composer-install:
	$(DOCKER_RUN) $(PHP) composer install


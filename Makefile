include .env
include .env.local

ifndef APP_ENV
	APP_ENV=dev
endif
ifndef APP_DEBUG
	APP_DEBUG=0
endif

ifeq ($(MAKECMDGOALS),tests)
	APP_ENV=test
	APP_DEBUG=0
endif

ifdef APP_ENV
	GLOBAL_ENVS:=$(GLOBAL_ENVS) -e APP_ENV=$(APP_ENV)
endif
ifdef APP_DEBUG
	GLOBAL_ENVS:=$(GLOBAL_ENVS) -e APP_DEBUG=$(APP_DEBUG)
endif

DOCKER_COMPOSE?=docker compose
DOCKER_COMPOSE_RUN?=$(DOCKER_COMPOSE) run --rm --no-deps $(GLOBAL_ENVS)
DOCKER_COMPOSE_EXEC?=$(DOCKER_COMPOSE) exec $(GLOBAL_ENVS)

PHP?=php
POSTGRES?=postgres

DOCKER_COMPOSE_RUN_PHP=$(DOCKER_COMPOSE_RUN) $(PHP)
DOCKER_COMPOSE_RUN_CONSOLE_PHP=$(DOCKER_COMPOSE_RUN) $(PHP) bin/console $(NO_DEBUG)

.PHONY: start
start: up composer-install db-init

stop:
	$(TARGET_HEADER)
	$(DOCKER_COMPOSE) stop

up:
	$(TARGET_HEADER)
	$(DOCKER_COMPOSE) up -d --build --remove-orphans --quiet-pull

composer-install:
	$(TARGET_HEADER)
	$(DOCKER_COMPOSE_RUN_PHP) composer install

db-init:
	$(TARGET_HEADER)
	$(DOCKER_COMPOSE_RUN_CONSOLE_PHP) doctrine:database:create --if-not-exists -n
	$(DOCKER_COMPOSE_RUN_CONSOLE_PHP) doctrine:migration:migrate -n
	$(DOCKER_COMPOSE_RUN_CONSOLE_PHP) doctrine:migration:sync-metadata-storage -n

start-worker:
	$(TARGET_HEADER)
	$(DOCKER_COMPOSE_RUN_CONSOLE_PHP) app:queue-worker ${QUEUE_NAME}

fix-cs:
	$(TARGET_HEADER)
	$(DOCKER_COMPOSE_RUN_PHP) vendor/bin/php-cs-fixer fix -v -n

phpstan:
	$(TARGET_HEADER)
	$(DOCKER_COMPOSE_RUN_PHP) vendor/bin/phpstan analyse --ansi

clear-cache:
	$(TARGET_HEADER)
	$(DOCKER_COMPOSE_RUN_CONSOLE_PHP) cache:clear -n

tests: up composer-install db-init
	$(TARGET_HEADER)
	$(DOCKER_COMPOSE_RUN_PHP) bin/phpunit
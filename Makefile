ifneq (,$(wildcard ./.env))
    include .env
    export
endif

# Executables (local)
DOCKER_COMP = docker compose

# Docker containers
PHP_EXEC = $(DOCKER_COMP) exec php-fpm
PHP_RUN = $(DOCKER_COMP) run --rm php-fpm

# Executables
PHP      = $(PHP_EXEC) php
COMPOSER = $(PHP_EXEC) composer
COMPOSER_RUN = $(PHP_RUN) composer
SYMFONY  = $(PHP) bin/console

# Misc
.DEFAULT_GOAL = help
.PHONY        : help build up start down logs sh composer vendor sf cc test

## —— 🎵 🐳 The Symfony Docker Makefile 🐳 🎵 ——————————————————————————————————
help: ## Outputs this help screen
	@awk 'BEGIN {FS = ":.*?## "; } /^[a-zA-Z0-9][a-zA-Z0-9_-]+:.*?## / { printf "\033[36m%-12s\033[0m %s\n", $$1, $$2 } /^## .*$$/ { printf "\033[33m%s\033[0m\n", substr($$0, 4) } ' $(MAKEFILE_LIST)

## —— Docker 🐳 ————————————————————————————————————————————————————————————————
build: ## Builds the Docker images
	@$(DOCKER_COMP) build --pull --no-cache

up: ## Start the docker hub in detached mode (no logs)
	@$(DOCKER_COMP) up --detach
	@echo "You can now access the application at http://localhost:$(NGINX_PORT)"

down: ## Stop the docker hub
	@$(DOCKER_COMP) down --remove-orphans

stop: ## Stop services
	@$(DOCKER_COMP) stop

start: ## Start services
	@$(DOCKER_COMP) start

restart: stop start ## Restart services

sh: ## Connect to the PHP container
	@$(PHP_EXEC) sh

env: ## Copy .env files from examples if not present
	@test -f .env     || (cp .env.example .env         && echo "Создан .env (infrastructure)")
	@test -f app/.env || (cp app/.env.example app/.env && echo "Создан app/.env (application)")

init: env down build install up migrate ## Build / rebuild application

## —— Composer 🧙 ——————————————————————————————————————————————————————————————
composer: ## Run composer, pass the parameter "c=" to run a given command, example: make composer c='req symfony/orm-pack'
	@$(eval c ?=)
	@$(COMPOSER) $(c)

vendor: ## Install vendors according to the current composer.lock file
vendor: c=install --prefer-dist --no-dev --no-progress --no-scripts --no-interaction
vendor: composer

install: ## Install dependencies without running the whole application.
	${COMPOSER_RUN} install

## —— Symfony 🎵 ———————————————————————————————————————————————————————————————
sf: ## List all Symfony commands or pass the parameter "c=" to run a given command, example: make sf c=about
	@$(eval c ?=)
	@$(SYMFONY) $(c)

cc: c=c:c ## Clear the cache
cc: sf

migrate: ## Run database migrations
	@$(SYMFONY) doctrine:migrations:migrate --no-interaction

## —— Tests 🧪 ————————————————————————————————————————————————————————————————
test-prepare: ## Create test database and run migrations
	@$(SYMFONY) doctrine:database:create --env=test --if-not-exists
	@$(SYMFONY) doctrine:migrations:migrate --env=test --no-interaction

test: test-prepare ## Run PHPUnit tests
	@$(PHP) bin/phpunit

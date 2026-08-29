COMPOSE_BASE := docker compose --env-file .env.docker -f docker-compose.yml
COMPOSE_DEV := $(COMPOSE_BASE) -f docker-compose.dev.yml --profile fpm
COMPOSE_PROD := $(COMPOSE_BASE) -f docker-compose.prod.yml --profile fpm

.DEFAULT_GOAL := help

.PHONY: help init key build up down restart ps logs shell artisan migrate fresh db-shell config test pint pint-fix stan stan-clear-cache audit rector rector-fix deptrac check composer vite boost-install boost-update boost-check prod-build prod-up prod-down prod-ps

help: ## Show available commands
	@awk 'BEGIN {FS = ":.*##"}; /^[a-zA-Z_-]+:.*##/ {printf "\033[36m%-12s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

init: ## Create local Docker environment file
	@test -f .env.docker || cp .env.docker.example .env.docker
	@echo "Set APP_KEY in .env.docker with: make key"

key: ## Print a generated Laravel APP_KEY
	@$(COMPOSE_DEV) run --rm --no-deps php php artisan key:generate --show

build: ## Build development images
	@$(COMPOSE_DEV) build

up: ## Start the development stack with Xdebug and Vite HMR
	@$(COMPOSE_DEV) up -d --build

down: ## Stop development containers and remove their network
	@$(COMPOSE_DEV) down

restart: ## Restart the development stack
	@$(COMPOSE_DEV) restart

ps: ## Show development service status
	@$(COMPOSE_DEV) ps

logs: ## Follow development logs
	@$(COMPOSE_DEV) logs -f

shell: ## Open a shell in the development PHP container
	@$(COMPOSE_DEV) exec php sh

artisan: ## Run Artisan, e.g. make artisan cmd="route:list"
	@$(COMPOSE_DEV) exec php php artisan $(cmd)

migrate: ## Run pending database migrations
	@$(COMPOSE_DEV) exec php php artisan migrate

fresh: ## Recreate database tables and run seeders
	@$(COMPOSE_DEV) exec php php artisan migrate:fresh --seed

db-shell: ## Open psql in the PostgreSQL container
	@$(COMPOSE_DEV) exec postgres sh -c 'psql -U "$$POSTGRES_USER" -d "$$POSTGRES_DB"'

test: ## Run Laravel tests in the development PHP container
	@$(COMPOSE_DEV) exec php php artisan test

pint: ## Run Laravel Pint in the development PHP container
	@$(COMPOSE_DEV) exec php vendor/bin/pint --test

pint-fix: ## Fix Laravel Pint style issues in the development PHP container
	@$(COMPOSE_DEV) exec php vendor/bin/pint

stan: ## Run Larastan static analysis in the development PHP container
	@$(COMPOSE_DEV) exec php vendor/bin/phpstan analyse --memory-limit=1G --no-progress

stan-clear-cache: ## Clear the Larastan result cache
	@$(COMPOSE_DEV) exec php vendor/bin/phpstan clear-result-cache

audit: ## Audit Composer dependencies for known vulnerabilities
	@$(COMPOSE_DEV) exec php composer audit --locked

rector: ## Preview Rector refactorings without changing files
	@$(COMPOSE_DEV) exec php vendor/bin/rector process --dry-run --no-progress-bar --memory-limit=1G

rector-fix: ## Apply Rector refactorings
	@$(COMPOSE_DEV) exec php vendor/bin/rector process --no-progress-bar --memory-limit=1G

deptrac: ## Enforce Domain module dependency boundaries
	@$(COMPOSE_DEV) exec php vendor/bin/deptrac --cache-file=storage/framework/deptrac.cache analyse

check: ## Run all code-quality checks and tests
	@$(MAKE) pint
	@$(MAKE) stan
	@$(MAKE) audit
	@$(MAKE) rector
	@$(MAKE) deptrac
	@$(MAKE) test

composer: ## Run Composer, e.g. make composer cmd="require vendor/package"
	@$(COMPOSE_DEV) exec php composer $(cmd)

vite: ## Follow Vite logs
	@$(COMPOSE_DEV) logs -f vite

boost-install: ## Install Boost dependencies in the PHP container and generate its project files
	@$(COMPOSE_DEV) exec php composer install --no-interaction --no-progress
	@$(COMPOSE_DEV) exec php php artisan boost:install --guidelines --skills --no-interaction

boost-update: ## Update Laravel Boost guidelines and skills in the PHP container
	@$(COMPOSE_DEV) exec php php artisan boost:update --no-interaction

boost-check: ## Verify Laravel Boost uses the PHP and PostgreSQL development stack
	@$(COMPOSE_DEV) exec php php -v
	@$(COMPOSE_DEV) exec php php artisan about --only=drivers --no-interaction
	@$(COMPOSE_DEV) exec php php artisan boost:mcp --help --no-interaction

config: ## Validate the resolved development Compose configuration
	@$(COMPOSE_DEV) config --quiet

prod-build: ## Build immutable production-like images
	@$(COMPOSE_PROD) build

prod-up: ## Start the production-like stack
	@$(COMPOSE_PROD) up -d --build

prod-down: ## Stop the production-like stack
	@$(COMPOSE_PROD) down

prod-ps: ## Show production-like service status
	@$(COMPOSE_PROD) ps

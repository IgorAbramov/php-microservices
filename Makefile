.PHONY: help install up down restart logs clean build start-consumers stop-consumers migrate migrate-product migrate-order test test-order test-product shell-product shell-order style-fix style-check style rector rector-fix

.DEFAULT_GOAL := help

help: ## Show this help message
	@echo "Available commands:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

install: ## Install dependencies for all services
	@echo "Installing dependencies for shared-bundle..."
	cd shared-bundle && composer install
	@echo "Installing dependencies for product-service..."
	cd product-service && composer install
	@echo "Installing dependencies for order-service..."
	cd order-service && composer install

build: ## Build Docker images
	docker-compose build

up: ## Start all services
	docker-compose up -d
	@echo "Services started:"
	@echo "  - Product Service: http://localhost:8001"
	@echo "  - Order Service: http://localhost:8002"
	@echo "  - RabbitMQ Management: http://localhost:15672 (admin/admin)"
	@echo "  - PostgreSQL Product: localhost:5433"
	@echo "  - PostgreSQL Order: localhost:5434"

down: ## Stop all services
	docker-compose down

restart: ## Restart all services
	docker-compose restart

clean: ## Stop all services and remove volumes
	docker-compose down -v
	docker system prune -f

migrate-product: ## Run migrations for product-service
	docker-compose exec product-service php bin/console doctrine:migrations:migrate --no-interaction

migrate-order: ## Run migrations for order-service
	docker-compose exec order-service php bin/console doctrine:migrations:migrate --no-interaction

migrate: migrate-product migrate-order ## Run migrations for all services

shell-product: ## Open shell in product-service container
	docker-compose exec product-service sh

shell-order: ## Open shell in order-service container
	docker-compose exec order-service sh

style-fix: ## Fix code style for all services
	@echo "Fixing code style for shared-bundle..."
	cd shared-bundle && vendor/bin/php-cs-fixer fix
	@echo "Fixing code style for product-service..."
	cd product-service && vendor/bin/php-cs-fixer fix
	@echo "Fixing code style for order-service..."
	cd order-service && vendor/bin/php-cs-fixer fix

style-check: ## Check code style for all services
	@echo "Checking code style for shared-bundle..."
	cd shared-bundle && vendor/bin/php-cs-fixer fix --dry-run --diff
	@echo "Checking code style for product-service..."
	cd product-service && vendor/bin/php-cs-fixer fix --dry-run --diff
	@echo "Checking code style for order-service..."
	cd order-service && vendor/bin/php-cs-fixer fix --dry-run --diff

rector: ## Run Rector (dry-run) for all services
	@echo "Running Rector for shared-bundle..."
	cd shared-bundle && vendor/bin/rector process --dry-run
	@echo "Running Rector for product-service..."
	cd product-service && vendor/bin/rector process --dry-run
	@echo "Running Rector for order-service..."
	cd order-service && vendor/bin/rector process --dry-run

rector-fix: ## Fix code with Rector for all services
	@echo "Fixing code with Rector for shared-bundle..."
	cd shared-bundle && vendor/bin/rector process
	@echo "Fixing code with Rector for product-service..."
	cd product-service && vendor/bin/rector process
	@echo "Fixing code with Rector for order-service..."
	cd order-service && vendor/bin/rector process

style: style-fix rector-fix ## Fix code style and run Rector for all services

test-order: ## Run tests for order-service
	@echo "Running tests for order-service..."
	cd order-service && vendor/bin/phpunit

test-product: ## Run tests for product-service
	@echo "Running tests for product-service..."
	cd product-service && vendor/bin/phpunit

test: test-order test-product ## Run tests for all services

start-consumers: ## Start message consumers in background
	@echo "Starting message consumers..."
	@docker-compose exec -d product-service php bin/console messenger:consume amqp --time-limit=3600
	@docker-compose exec -d order-service php bin/console messenger:consume amqp --time-limit=3600
	@sleep 2
	@echo "Message consumers started in background"

stop-consumers: ## Stop message consumers
	@echo "Stopping message consumers..."
	@-docker-compose exec product-service pkill -f "messenger:consume" || true
	@-docker-compose exec order-service pkill -f "messenger:consume" || true
	@echo "Message consumers stopped"
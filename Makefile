.PHONY: help install up down restart logs clean build

help:
	@echo "Available commands:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

install:
	@echo "Installing dependencies for shared-bundle..."
	cd shared-bundle && composer install
	@echo "Installing dependencies for product-service..."
	cd product-service && composer install
	@echo "Installing dependencies for order-service..."
	cd order-service && composer install

build:
	docker-compose build

up:
	docker-compose up -d
	@echo "Services started:"
	@echo "  - Product Service: http://localhost:8001"
	@echo "  - Order Service: http://localhost:8002"
	@echo "  - RabbitMQ Management: http://localhost:15672 (admin/admin)"
	@echo "  - PostgreSQL Product: localhost:5433"
	@echo "  - PostgreSQL Order: localhost:5434"

down:
	docker-compose down

restart:
	docker-compose restart

clean:
	docker-compose down -v
	docker system prune -f

migrate-product:
	docker-compose exec product-service php bin/console doctrine:migrations:migrate --no-interaction

migrate-order:
	docker-compose exec order-service php bin/console doctrine:migrations:migrate --no-interaction

migrate: migrate-product migrate-order

shell-product:
	docker-compose exec product-service sh

shell-order:
	docker-compose exec order-service sh

style-fix:
	@echo "Fixing code style for shared-bundle..."
	cd shared-bundle && vendor/bin/php-cs-fixer fix
	@echo "Fixing code style for product-service..."
	cd product-service && vendor/bin/php-cs-fixer fix
	@echo "Fixing code style for order-service..."
	cd order-service && vendor/bin/php-cs-fixer fix

style-check:
	@echo "Checking code style for shared-bundle..."
	cd shared-bundle && vendor/bin/php-cs-fixer fix --dry-run --diff
	@echo "Checking code style for product-service..."
	cd product-service && vendor/bin/php-cs-fixer fix --dry-run --diff
	@echo "Checking code style for order-service..."
	cd order-service && vendor/bin/php-cs-fixer fix --dry-run --diff

rector:
	@echo "Running Rector for shared-bundle..."
	cd shared-bundle && vendor/bin/rector process --dry-run
	@echo "Running Rector for product-service..."
	cd product-service && vendor/bin/rector process --dry-run
	@echo "Running Rector for order-service..."
	cd order-service && vendor/bin/rector process --dry-run

rector-fix:
	@echo "Fixing code with Rector for shared-bundle..."
	cd shared-bundle && vendor/bin/rector process
	@echo "Fixing code with Rector for product-service..."
	cd product-service && vendor/bin/rector process
	@echo "Fixing code with Rector for order-service..."
	cd order-service && vendor/bin/rector process

style: style-fix rector-fix


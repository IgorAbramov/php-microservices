# PHP Microservices - Product and Order Services

Monorepo with two Symfony 7.3 microservices: Product Service and Order Service.

## Project Structure

```
php-microservices/
├── shared-bundle/          # Shared bundle for both microservices
├── product-service/        # Product Service (Symfony 7.3)
├── order-service/          # Order Service (Symfony 7.3)
├── docs/                   # Documentation and diagrams
│   └── microservices-communication.puml  # PlantUML sequence diagram
├── docker-compose.yml     # Docker configuration
├── Makefile               # Commands for project management
└── README.md
```

## Requirements

- Docker and Docker Compose
- Make (optional, for using Makefile)

## Quick Start

### 1. Install Dependencies

```bash
make install
```

Or manually:
```bash
cd shared-bundle && composer install
cd ../product-service && composer install
cd ../order-service && composer install
```

### 2. Start All Services

```bash
make up
```

Or:
```bash
docker-compose up -d
```

### 3. Run Migrations

```bash
make migrate
```

## Available Services

- **Product Service**: http://localhost:8001
- **Order Service**: http://localhost:8002
- **RabbitMQ Management**: http://localhost:15672 (admin/admin)
- **PostgreSQL Product**: localhost:5433
- **PostgreSQL Order**: localhost:5434

## Documentation

### Architecture Diagrams

The project includes PlantUML sequence diagrams that describe the communication flow between microservices and the product quantity update process.

**Location**: `docs/microservices-communication.puml`
# Microservices Shared Bundle

Shared bundle for Product and Order microservices.

## Installation

Add the bundle to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../shared-bundle"
        }
    ],
    "require": {
        "microservices/shared-bundle": "*"
    }
}
```

Then run:

```bash
composer require microservices/shared-bundle
```

## Configuration

Register the bundle in `config/bundles.php`:

```php
return [
    Microservices\SharedBundle\MicroservicesSharedBundle::class => ['all' => true],
];
```

## Usage

The bundle provides:

- `Product` entity (mapped superclass) with UUID support
- `ProductDTO` for data transfer
- `ProductMessage` for RabbitMQ messaging
- RabbitMQ configuration


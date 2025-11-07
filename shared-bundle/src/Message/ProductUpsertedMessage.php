<?php

declare(strict_types=1);

namespace Microservices\SharedBundle\Message;

class ProductUpsertedMessage
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly float $price,
        public readonly int $quantity
    ) {
    }
}

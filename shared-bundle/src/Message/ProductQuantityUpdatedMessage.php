<?php

declare(strict_types=1);

namespace Microservices\SharedBundle\Message;

class ProductQuantityUpdatedMessage
{
    public function __construct(
        public readonly string $productId,
        public readonly int $quantity
    ) {
    }
}

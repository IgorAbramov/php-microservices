<?php

declare(strict_types=1);

namespace Microservices\SharedBundle\Message;

class OrderPlacedMessage
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $productId,
        public readonly int $quantityOrdered
    ) {
    }
}

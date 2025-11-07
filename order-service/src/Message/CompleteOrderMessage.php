<?php

declare(strict_types=1);

namespace App\Message;

class CompleteOrderMessage
{
    public function __construct(
        public readonly string $orderId
    ) {
    }
}

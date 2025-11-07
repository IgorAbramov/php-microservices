<?php

declare(strict_types=1);

namespace App\Message;

class OrderTransitionMessage
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $transition
    ) {
    }
}

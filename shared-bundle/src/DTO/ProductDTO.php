<?php

declare(strict_types=1);

namespace Microservices\SharedBundle\DTO;

use Microservices\SharedBundle\Message\ProductUpsertedMessage;

class ProductDTO
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $name = null,
        public readonly ?float $price = null,
        public readonly ?int $quantity = null
    ) {
    }

    public static function createFromMessage(ProductUpsertedMessage $message): self
    {
        return new self(
            id: $message->id,
            name: $message->name,
            price: $message->price,
            quantity: $message->quantity,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price ?? null,
            'quantity' => $this->quantity,
        ];
    }
}

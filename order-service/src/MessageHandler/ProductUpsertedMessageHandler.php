<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Service\Product\ProductSyncService;
use Microservices\SharedBundle\DTO\ProductDTO;
use Microservices\SharedBundle\Message\ProductUpsertedMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ProductUpsertedMessageHandler
{
    public function __construct(
        private ProductSyncService $productSyncService
    ) {
    }

    public function __invoke(ProductUpsertedMessage $message): void
    {
        $productDTO = ProductDTO::createFromMessage($message);
        $this->productSyncService->syncProduct($productDTO);
    }
}

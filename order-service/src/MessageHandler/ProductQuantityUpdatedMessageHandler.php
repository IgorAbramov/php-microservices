<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Service\Product\ProductQuantityUpdateService;
use Microservices\SharedBundle\Message\ProductQuantityUpdatedMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ProductQuantityUpdatedMessageHandler
{
    public function __construct(
        private ProductQuantityUpdateService $productQuantityUpdateService
    ) {
    }

    public function __invoke(ProductQuantityUpdatedMessage $message): void
    {
        $this->productQuantityUpdateService->updateProductQuantity($message);
    }
}

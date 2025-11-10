<?php

declare(strict_types=1);

namespace App\Service\Product;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Microservices\SharedBundle\Message\ProductQuantityUpdatedMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

readonly class ProductQuantityUpdateService
{
    public function __construct(
        private ProductRepository $productRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    public function updateProductQuantity(ProductQuantityUpdatedMessage $message): void
    {
        $productId = Uuid::fromString($message->productId);
        $this->logger->info(\sprintf('Updating product %s quantity to %d', $productId->toString(), $message->quantity));

        $product = $this->productRepository->find($productId);

        if (! $product instanceof Product) {
            $this->logger->warning(\sprintf('Product %s not found for quantity update', $productId->toString()));

            return;
        }

        $product->setQuantity($message->quantity);
        $this->entityManager->flush();

        $this->logger->info(\sprintf('Product %s quantity updated to %d', $productId->toString(), $message->quantity));
    }
}

<?php

declare(strict_types=1);

namespace App\Service\Product;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Microservices\SharedBundle\DTO\ProductDTO;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

readonly class ProductSyncService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository,
        private LoggerInterface $logger
    ) {
    }

    public function syncProduct(ProductDTO $productDTO): void
    {
        $productId = Uuid::fromString($productDTO->id);
        $this->logger->info(\sprintf('Product %s sync started...', $productId));

        $product = $this->productRepository->findById($productId);

        if (! $product instanceof Product) {
            $product = new Product(id: $productId, name: $productDTO->name, price: $productDTO->price, quantity: $productDTO->quantity);
            $this->logger->info(\sprintf('Product %s created', $productId));
        } else {
            $product->setName($productDTO->name);
            $product->setPrice($productDTO->price);
            $product->setQuantity($productDTO->quantity);
            $this->logger->info(\sprintf('Product %s updated', $productId));
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->logger->info(\sprintf('Product %s saved', $productId));
    }
}

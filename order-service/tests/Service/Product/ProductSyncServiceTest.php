<?php

declare(strict_types=1);

namespace App\Tests\Service\Product;

use App\Entity\Product as AppProduct;
use App\Repository\ProductRepository;
use App\Service\Product\ProductSyncService;
use Doctrine\ORM\EntityManagerInterface;
use Microservices\SharedBundle\DTO\ProductDTO;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class ProductSyncServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ProductRepository $productRepository;
    private LoggerInterface $logger;
    private ProductSyncService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new ProductSyncService(
            $this->entityManager,
            $this->productRepository,
            $this->logger
        );
    }

    public function testSyncProductCreateNew(): void
    {
        $productId = Uuid::v7();
        $productDTO = new ProductDTO(
            id: $productId->toString(),
            name: 'Test Product',
            price: 99.99,
            quantity: 10
        );

        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn(null);

        $this->logger
            ->expects($this->exactly(3))
            ->method('info');

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->service->syncProduct($productDTO);
    }

    public function testSyncProductUpdateExisting(): void
    {
        $productId = Uuid::v7();
        $productDTO = new ProductDTO(
            id: $productId->toString(),
            name: 'Updated Product',
            price: 149.99,
            quantity: 20
        );

        $existingProduct = $this->createMock(AppProduct::class);
        $existingProduct->method('getId')->willReturn($productId);
        $existingProduct->expects($this->once())->method('setName')->with('Updated Product');
        $existingProduct->expects($this->once())->method('setPrice')->with(149.99);
        $existingProduct->expects($this->once())->method('setQuantity')->with(20);

        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($existingProduct);

        $this->logger
            ->expects($this->exactly(3))
            ->method('info');

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($existingProduct);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->service->syncProduct($productDTO);
    }

    public function testSyncProductWithNullId(): void
    {
        $productDTO = new ProductDTO(
            id: null,
            name: 'Test Product',
            price: 99.99,
            quantity: 10
        );

        $this->expectException(\TypeError::class);

        $this->service->syncProduct($productDTO);
    }

    public function testSyncProductWithZeroQuantity(): void
    {
        $productId = Uuid::v7();
        $productDTO = new ProductDTO(
            id: $productId->toString(),
            name: 'Test Product',
            price: 99.99,
            quantity: 0
        );

        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn(null);

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->service->syncProduct($productDTO);
    }
}

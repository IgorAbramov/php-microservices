<?php

declare(strict_types=1);

namespace App\Tests\Service\Product;

use App\Entity\Product as AppProduct;
use App\Repository\ProductRepository;
use App\Service\Product\ProductQuantityUpdateService;
use Doctrine\ORM\EntityManagerInterface;
use Microservices\SharedBundle\Message\ProductQuantityUpdatedMessage;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class ProductQuantityUpdateServiceTest extends TestCase
{
    private ProductRepository $productRepository;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private ProductQuantityUpdateService $service;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new ProductQuantityUpdateService(
            $this->productRepository,
            $this->entityManager,
            $this->logger
        );
    }

    public function testUpdateProductQuantity(): void
    {
        $productId = Uuid::v7();
        $message = new ProductQuantityUpdatedMessage(
            productId: $productId->toString(),
            quantity: 15
        );

        $product = $this->createMock(AppProduct::class);
        $product->expects($this->once())->method('setQuantity')->with(15);

        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);

        $this->logger
            ->expects($this->exactly(2))
            ->method('info');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->service->updateProductQuantity($message);
    }

    public function testUpdateProductQuantityWhenProductNotFound(): void
    {
        $productId = Uuid::v7();
        $message = new ProductQuantityUpdatedMessage(
            productId: $productId->toString(),
            quantity: 15
        );

        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn(null);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Updating product '.$productId->toString().' quantity to 15'));

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Product '.$productId->toString().' not found'));

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        $this->service->updateProductQuantity($message);
    }

    public function testUpdateProductQuantityWithZeroQuantity(): void
    {
        $productId = Uuid::v7();
        $message = new ProductQuantityUpdatedMessage(
            productId: $productId->toString(),
            quantity: 0
        );

        $product = $this->createMock(AppProduct::class);
        $product->expects($this->once())->method('setQuantity')->with(0);

        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);

        $this->logger
            ->expects($this->exactly(2))
            ->method('info');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->service->updateProductQuantity($message);
    }

    public function testUpdateProductQuantityWithNegativeQuantity(): void
    {
        $productId = Uuid::v7();
        $message = new ProductQuantityUpdatedMessage(
            productId: $productId->toString(),
            quantity: -5
        );

        $product = $this->createMock(AppProduct::class);
        $product->expects($this->once())->method('setQuantity')->with(-5);

        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->service->updateProductQuantity($message);
    }
}

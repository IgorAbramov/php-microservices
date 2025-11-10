<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\OrderProcessingService;
use Doctrine\ORM\EntityManagerInterface;
use Microservices\SharedBundle\Message\OrderPlacedMessage;
use Microservices\SharedBundle\Message\ProductQuantityUpdatedMessage;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final class OrderProcessingServiceTest extends TestCase
{
    private ProductRepository $productRepository;
    private EntityManagerInterface $entityManager;
    private MessageBusInterface $messageBus;
    private LoggerInterface $logger;
    private OrderProcessingService $service;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new OrderProcessingService(
            $this->productRepository,
            $this->entityManager,
            $this->messageBus,
            $this->logger
        );
    }

    public function testProcessOrderPlacedHappyPath(): void
    {
        $orderId = Uuid::v7();
        $productId = Uuid::v7();
        $message = new OrderPlacedMessage(
            orderId: $orderId->toString(),
            productId: $productId->toString(),
            quantityOrdered: 5
        );

        $product = $this->createMock(Product::class);
        $product->method('getQuantity')->willReturn(10);
        $product->expects($this->once())->method('setQuantity')->with(5);

        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);

        $this->logger
            ->expects($this->exactly(3))
            ->method('info');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(function ($msg) use ($productId) {
                    return $msg instanceof ProductQuantityUpdatedMessage
                        && $msg->productId === $productId->toString()
                        && 5 === $msg->quantity;
                }),
                $this->callback(function ($stamps) {
                    return \is_array($stamps)
                        && 1 === \count($stamps)
                        && $stamps[0] instanceof AmqpStamp;
                })
            )
            ->willReturn(new Envelope(new ProductQuantityUpdatedMessage($productId->toString(), 5)));

        $this->service->processOrderPlaced($message);
    }

    public function testProcessOrderPlacedWhenProductNotFound(): void
    {
        $orderId = Uuid::v7();
        $productId = Uuid::v7();
        $message = new OrderPlacedMessage(
            orderId: $orderId->toString(),
            productId: $productId->toString(),
            quantityOrdered: 5
        );

        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn(null);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Processing order '.$orderId->toString()));

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Product '.$productId->toString().' not found'));

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        $this->messageBus
            ->expects($this->never())
            ->method('dispatch');

        $this->service->processOrderPlaced($message);
    }

    public function testProcessOrderPlacedWhenInsufficientQuantity(): void
    {
        $orderId = Uuid::v7();
        $productId = Uuid::v7();
        $message = new OrderPlacedMessage(
            orderId: $orderId->toString(),
            productId: $productId->toString(),
            quantityOrdered: 10
        );

        $product = $this->createMock(Product::class);
        $product->method('getQuantity')->willReturn(5);

        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Processing order '.$orderId->toString()));

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Insufficient quantity'));

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        $this->messageBus
            ->expects($this->never())
            ->method('dispatch');

        $product->expects($this->never())->method('setQuantity');

        $this->service->processOrderPlaced($message);
    }

    public function testProcessOrderPlacedWithZeroQuantity(): void
    {
        $orderId = Uuid::v7();
        $productId = Uuid::v7();
        $message = new OrderPlacedMessage(
            orderId: $orderId->toString(),
            productId: $productId->toString(),
            quantityOrdered: 0
        );

        $product = $this->createMock(Product::class);
        $product->method('getQuantity')->willReturn(10);
        $product->expects($this->once())->method('setQuantity')->with(10);

        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(new Envelope(new ProductQuantityUpdatedMessage($productId->toString(), 10)));

        $this->service->processOrderPlaced($message);
    }

    public function testProcessOrderPlacedWithNullProductQuantity(): void
    {
        $orderId = Uuid::v7();
        $productId = Uuid::v7();
        $message = new OrderPlacedMessage(
            orderId: $orderId->toString(),
            productId: $productId->toString(),
            quantityOrdered: 5
        );

        $product = $this->createMock(Product::class);
        $product->method('getQuantity')->willReturn(null);

        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Insufficient quantity'));

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        $this->messageBus
            ->expects($this->never())
            ->method('dispatch');

        $this->service->processOrderPlaced($message);
    }
}

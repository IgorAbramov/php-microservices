<?php

declare(strict_types=1);

namespace App\Tests\StateProcessor;

use ApiPlatform\Metadata\Operation;
use App\Entity\Product;
use App\StateProcessor\ProductStateProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Microservices\SharedBundle\Message\ProductUpsertedMessage;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final class ProductStateProcessorTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private MessageBusInterface $messageBus;
    private LoggerInterface $logger;
    private ProductStateProcessor $processor;
    private Operation $operation;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->processor = new ProductStateProcessor(
            $this->entityManager,
            $this->messageBus,
            $this->logger
        );
        $this->operation = $this->createMock(Operation::class);
    }

    public function testProcessHappyPath(): void
    {
        $productId = Uuid::v7();
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn($productId);
        $product->method('getName')->willReturn('Test Product');
        $product->method('getPrice')->willReturn(99.99);
        $product->method('getQuantity')->willReturn(10);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($product);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Sending ProductUpserted message for product '.$productId->toString()));

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(function ($message) use ($productId) {
                    return $message instanceof ProductUpsertedMessage
                        && $message->id === $productId->toString()
                        && 'Test Product' === $message->name
                        && 99.99 === $message->price
                        && 10 === $message->quantity;
                }),
                $this->callback(function ($stamps) {
                    return \is_array($stamps)
                        && 1 === \count($stamps)
                        && $stamps[0] instanceof AmqpStamp;
                })
            )
            ->willReturn(new Envelope(new ProductUpsertedMessage($productId->toString(), 'Test Product', 99.99, 10)));

        $result = $this->processor->process($product, $this->operation);

        $this->assertSame($product, $result);
    }

    public function testProcessWithNonProductData(): void
    {
        $nonProduct = new \stdClass();

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        $this->messageBus
            ->expects($this->never())
            ->method('dispatch');

        $result = $this->processor->process($nonProduct, $this->operation);

        $this->assertSame($nonProduct, $result);
    }

    public function testProcessWithNullProductId(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(null);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($product);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Product Id should not be null');

        $this->processor->process($product, $this->operation);
    }

    public function testProcessWithNullProductName(): void
    {
        $productId = Uuid::v7();
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn($productId);
        $product->method('getName')->willReturn(null);
        $product->method('getPrice')->willReturn(99.99);
        $product->method('getQuantity')->willReturn(10);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($product);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(function ($message) use ($productId) {
                    return $message instanceof ProductUpsertedMessage
                        && $message->id === $productId->toString()
                        && '' === $message->name
                        && 99.99 === $message->price
                        && 10 === $message->quantity;
                }),
                $this->anything()
            )
            ->willReturn(new Envelope(new ProductUpsertedMessage($productId->toString(), '', 99.99, 10)));

        $result = $this->processor->process($product, $this->operation);

        $this->assertSame($product, $result);
    }

    public function testProcessWithNullProductPrice(): void
    {
        $productId = Uuid::v7();
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn($productId);
        $product->method('getName')->willReturn('Test Product');
        $product->method('getPrice')->willReturn(null);
        $product->method('getQuantity')->willReturn(10);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($product);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(function ($message) use ($productId) {
                    return $message instanceof ProductUpsertedMessage
                        && $message->id === $productId->toString()
                        && 'Test Product' === $message->name
                        && 0.0 === $message->price
                        && 10 === $message->quantity;
                }),
                $this->anything()
            )
            ->willReturn(new Envelope(new ProductUpsertedMessage($productId->toString(), 'Test Product', 0.0, 10)));

        $result = $this->processor->process($product, $this->operation);

        $this->assertSame($product, $result);
    }

    public function testProcessWithNullProductQuantity(): void
    {
        $productId = Uuid::v7();
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn($productId);
        $product->method('getName')->willReturn('Test Product');
        $product->method('getPrice')->willReturn(99.99);
        $product->method('getQuantity')->willReturn(null);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($product);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(function ($message) use ($productId) {
                    return $message instanceof ProductUpsertedMessage
                        && $message->id === $productId->toString()
                        && 'Test Product' === $message->name
                        && 99.99 === $message->price
                        && 0 === $message->quantity;
                }),
                $this->anything()
            )
            ->willReturn(new Envelope(new ProductUpsertedMessage($productId->toString(), 'Test Product', 99.99, 0)));

        $result = $this->processor->process($product, $this->operation);

        $this->assertSame($product, $result);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Service\Order;

use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Message\CompleteOrderMessage;
use App\Repository\OrderRepository;
use App\Service\Order\OrderCompletionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class OrderCompletionServiceTest extends TestCase
{
    private OrderRepository $orderRepository;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private OrderCompletionService $service;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new OrderCompletionService(
            $this->orderRepository,
            $this->entityManager,
            $this->logger
        );
    }

    public function testCompleteOrder(): void
    {
        $orderId = Uuid::v7();
        $message = new CompleteOrderMessage($orderId->toString());
        $order = $this->createMock(Order::class);

        $order->method('getOrderStatus')->willReturn(OrderStatus::PROCESSING);
        $order->expects($this->once())->method('setOrderStatus')->with(OrderStatus::COMPLETED);

        $this->orderRepository
            ->expects($this->once())
            ->method('find')
            ->with($orderId->toString())
            ->willReturn($order);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Order '.$orderId->toString().' completed'));

        $this->service->completeOrder($message);
    }

    public function testCompleteOrderWhenOrderNotFound(): void
    {
        $orderId = Uuid::v7();
        $message = new CompleteOrderMessage($orderId->toString());

        $this->orderRepository
            ->expects($this->once())
            ->method('find')
            ->with($orderId->toString())
            ->willReturn(null);

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Order '.$orderId->toString().' not found'));

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        $this->service->completeOrder($message);
    }

    public function testCompleteOrderWhenOrderAlreadyCompleted(): void
    {
        $orderId = Uuid::v7();
        $message = new CompleteOrderMessage($orderId->toString());
        $order = $this->createMock(Order::class);

        $order->method('getOrderStatus')->willReturn(OrderStatus::COMPLETED);

        $this->orderRepository
            ->expects($this->once())
            ->method('find')
            ->with($orderId->toString())
            ->willReturn($order);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains('already in status'));

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        $order->expects($this->never())->method('setOrderStatus');

        $this->service->completeOrder($message);
    }

    public function testCompleteOrderWhenOrderIsCancelled(): void
    {
        $orderId = Uuid::v7();
        $message = new CompleteOrderMessage($orderId->toString());
        $order = $this->createMock(Order::class);

        $order->method('getOrderStatus')->willReturn(OrderStatus::CANCELLED);

        $this->orderRepository
            ->expects($this->once())
            ->method('find')
            ->with($orderId->toString())
            ->willReturn($order);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains('already in status'));

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        $order->expects($this->never())->method('setOrderStatus');

        $this->service->completeOrder($message);
    }
}

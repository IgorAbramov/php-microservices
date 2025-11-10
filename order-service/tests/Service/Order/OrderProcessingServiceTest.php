<?php

declare(strict_types=1);

namespace App\Tests\Service\Order;

use App\Entity\Order;
use App\Message\CompleteOrderMessage;
use App\Service\Order\OrderProcessingService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Uid\Uuid;

final class OrderProcessingServiceTest extends TestCase
{
    private MessageBusInterface $messageBus;
    private LoggerInterface $logger;
    private OrderProcessingService $service;

    protected function setUp(): void
    {
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new OrderProcessingService($this->messageBus, $this->logger);
    }

    public function testProcessOrder(): void
    {
        $orderId = Uuid::v7();
        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn($orderId);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Processing order'));

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(function ($message) use ($orderId) {
                    return $message instanceof CompleteOrderMessage
                        && $message->orderId === $orderId->toString();
                }),
                $this->callback(function ($stamps) {
                    if (! \is_array($stamps) || 2 !== \count($stamps)) {
                        return false;
                    }
                    $hasAmqpStamp = false;
                    $hasDelayStamp = false;
                    foreach ($stamps as $stamp) {
                        if ($stamp instanceof AmqpStamp) {
                            $hasAmqpStamp = true;
                        }
                        if ($stamp instanceof DelayStamp) {
                            $hasDelayStamp = true;
                        }
                    }

                    return $hasAmqpStamp && $hasDelayStamp;
                })
            )
            ->willReturn(new Envelope(new CompleteOrderMessage($orderId->toString())));

        $this->service->processOrder($order);
    }

    public function testProcessOrderLogsCorrectMessage(): void
    {
        $orderId = Uuid::v7();
        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn($orderId);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains($orderId->toString()));

        $this->messageBus
            ->method('dispatch')
            ->willReturn(new Envelope(new CompleteOrderMessage($orderId->toString())));

        $this->service->processOrder($order);
    }
}

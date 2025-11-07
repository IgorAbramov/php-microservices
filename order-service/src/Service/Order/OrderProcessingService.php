<?php

declare(strict_types=1);

namespace App\Service\Order;

use App\Entity\Order;
use App\Message\CompleteOrderMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

final readonly class OrderProcessingService
{
    private const ORDER_COMPLETION_DELAY_SECONDS = 10;

    public function __construct(
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger
    ) {
    }

    public function processOrder(Order $order): void
    {
        $orderId = $order->getId()->toString();

        $this->logger->info(\sprintf('Processing order %s, scheduling completion in %d seconds', $orderId, self::ORDER_COMPLETION_DELAY_SECONDS));

        $message = new CompleteOrderMessage($orderId);
        $this->messageBus->dispatch($message, [
            new AmqpStamp('order.completion'),
            new DelayStamp(self::ORDER_COMPLETION_DELAY_SECONDS * 1000),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Service\Order;

use App\Enum\OrderStatus;
use App\Message\CompleteOrderMessage;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

readonly class OrderCompletionService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    public function completeOrder(CompleteOrderMessage $message): void
    {
        $order = $this->orderRepository->find($message->orderId);

        if (null === $order) {
            $this->logger->warning(\sprintf('Order %s not found for completion', $message->orderId));

            return;
        }

        if (OrderStatus::PROCESSING !== $order->getOrderStatus()) {
            $this->logger->info(\sprintf('Order %s is already in status %s, skipping completion', $message->orderId, $order->getOrderStatus()->value));

            return;
        }

        $order->setOrderStatus(OrderStatus::COMPLETED);
        $this->entityManager->flush();

        $this->logger->info(\sprintf('Order %s completed', $message->orderId));
    }
}

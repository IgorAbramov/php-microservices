<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\OrderCreatedEvent;
use App\Service\Order\OrderProcessingService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class OrderCreatedEventListener implements EventSubscriberInterface
{
    public function __construct(
        private OrderProcessingService $orderProcessingService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OrderCreatedEvent::class => 'onOrderCreated',
        ];
    }

    public function onOrderCreated(OrderCreatedEvent $event): void
    {
        $this->orderProcessingService->processOrder($event->order);
    }
}

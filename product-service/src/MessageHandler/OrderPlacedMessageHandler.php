<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Service\OrderProcessingService;
use Microservices\SharedBundle\Message\OrderPlacedMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;

#[AsMessageHandler]
readonly class OrderPlacedMessageHandler
{
    public function __construct(
        private OrderProcessingService $orderProcessingService
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(OrderPlacedMessage $message): void
    {
        $this->orderProcessingService->processOrderPlaced($message);
    }
}

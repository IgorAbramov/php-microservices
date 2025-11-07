<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CompleteOrderMessage;
use App\Service\Order\OrderCompletionService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class CompleteOrderMessageHandler
{
    public function __construct(
        private OrderCompletionService $orderCompletionService
    ) {
    }

    public function __invoke(CompleteOrderMessage $message): void
    {
        $this->orderCompletionService->completeOrder($message);
    }
}

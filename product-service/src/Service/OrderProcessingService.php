<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Microservices\SharedBundle\Message\OrderPlacedMessage;
use Microservices\SharedBundle\Message\ProductQuantityUpdatedMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

readonly class OrderProcessingService
{
    public function __construct(
        private ProductRepository $productRepository,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function processOrderPlaced(OrderPlacedMessage $message): void
    {
        $productId = Uuid::fromString($message->productId);
        $this->logger->info(\sprintf('Processing order %s: decreasing product %s quantity by %d', $message->orderId, $productId->toString(), $message->quantityOrdered));

        $product = $this->productRepository->find($productId);

        if (! $product instanceof Product) {
            $this->logger->warning(\sprintf('Product %s not found for order %s', $productId->toString(), $message->orderId));

            return;
        }

        $currentQuantity = $product->getQuantity() ?? 0;

        if ($currentQuantity < $message->quantityOrdered) {
            $this->logger->warning(\sprintf('Insufficient quantity for product %s: requested %d, available %d', $productId->toString(), $message->quantityOrdered, $currentQuantity));

            return;
        }

        $newQuantity = $currentQuantity - $message->quantityOrdered;
        $product->setQuantity($newQuantity);
        $this->entityManager->flush();

        $this->logger->info(\sprintf('Product %s quantity decreased from %d to %d for order %s', $productId->toString(), $currentQuantity, $newQuantity, $message->orderId));

        $quantityUpdateMessage = new ProductQuantityUpdatedMessage(
            productId: $productId->toString(),
            quantity: $newQuantity
        );

        $this->messageBus->dispatch($quantityUpdateMessage, [new AmqpStamp('product.quantity.updated')]);
        $this->logger->info(\sprintf('Product quantity update message sent for product %s', $productId->toString()));
    }
}

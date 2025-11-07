<?php

declare(strict_types=1);

namespace App\StateProcessor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Microservices\SharedBundle\Message\ProductUpsertedMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final readonly class ProductStateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (! $data instanceof Product) {
            return $data;
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        $this->publishMessage($data);

        return $data;
    }

    /**
     * @throws ExceptionInterface
     */
    private function publishMessage(Product $product): void
    {
        $id = $product->getId();

        if (! $id instanceof Uuid) {
            throw new \LogicException('Product Id should not be null');
        }

        $this->logger->info(\sprintf('Sending ProductUpserted message for product %s to the queue...', $id));

        $message = new ProductUpsertedMessage(
            id: $id->toString(),
            name: $product->getName() ?? '',
            price: $product->getPrice() ?? 0.0,
            quantity: $product->getQuantity() ?? 0
        );

        $this->messageBus->dispatch($message, [new AmqpStamp('product.upserted')]);
    }
}

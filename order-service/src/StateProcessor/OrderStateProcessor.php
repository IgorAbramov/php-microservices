<?php

declare(strict_types=1);

namespace App\StateProcessor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Order;
use App\Entity\Product;
use App\Enum\OrderStatus;
use App\Event\OrderCreatedEvent;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Microservices\SharedBundle\Message\OrderPlacedMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

readonly class OrderStateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository,
        private EventDispatcherInterface $eventDispatcher,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (! $data instanceof Order) {
            return $data;
        }

        $productId = $data->getProductId();

        if (null === $productId) {
            throw new BadRequestHttpException('productId is required');
        }

        $product = $this->productRepository->find($productId);

        if (! $product instanceof Product) {
            throw new BadRequestHttpException('Product not found');
        }

        if ($data->getQuantityOrdered() > $product->getQuantity()) {
            throw new BadRequestHttpException('Insufficient product quantity');
        }

        $data->setProduct($product);
        $data->setOrderStatus(OrderStatus::PROCESSING);

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        $orderId = $data->getId();

        $orderPlacedMessage = new OrderPlacedMessage(
            orderId: $orderId->toString(),
            productId: $productId instanceof Uuid ? $productId->toString() : $productId,
            quantityOrdered: $data->getQuantityOrdered()
        );

        $this->messageBus->dispatch($orderPlacedMessage, [new AmqpStamp('order.placed')]);
        $this->logger->info(\sprintf('OrderPlaced message sent for order %s, product %s', $orderId->toString(), $productId instanceof Uuid ? $productId->toString() : $productId));

        $event = new OrderCreatedEvent($data);
        $this->eventDispatcher->dispatch($event, OrderCreatedEvent::class);

        return $data;
    }
}

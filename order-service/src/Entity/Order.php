<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Enum\OrderStatus;
use App\StateProcessor\OrderStateProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['order:read']]),
        new Get(normalizationContext: ['groups' => ['order:read']]),
        new Post(
            denormalizationContext: ['groups' => ['order:write']],
            processor: OrderStateProcessor::class
        ),
    ]
)]
class Order
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Product::class)]
        #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false)]
        #[Groups(['order:read'])]
        private ?Product $product = null,
        #[ORM\Column(type: 'string', length: 255)]
        #[Groups(['order:read', 'order:write'])]
        private ?string $customerName = null,
        #[ORM\Column(type: 'integer')]
        #[Groups(['order:read', 'order:write'])]
        private ?int $quantityOrdered = null,
        #[Groups(['order:write'])]
        private ?string $productId = null,
        #[ORM\Column(type: Types::STRING, length: 50, enumType: OrderStatus::class)]
        #[Groups(['order:read'])]
        private OrderStatus $orderStatus = OrderStatus::PROCESSING
    ) {
        $this->id = Uuid::v7();
    }

    #[ApiProperty(identifier: true)]
    #[Groups(['order:read'])]
    #[SerializedName('orderId')]
    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    #[Groups(['order:read'])]
    #[SerializedName('product')]
    public function getProductData(): ?array
    {
        if (! $this->product instanceof Product) {
            return null;
        }

        return [
            'id' => $this->product->getId()?->toString(),
            'name' => $this->product->getName(),
            'price' => $this->product->getPrice(),
            'quantity' => $this->product->getQuantity(),
        ];
    }

    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }

    public function setCustomerName(string $customerName): self
    {
        $this->customerName = $customerName;

        return $this;
    }

    #[Groups(['order:read'])]
    #[SerializedName('quantityOrdered')]
    public function getQuantityOrdered(): ?int
    {
        return $this->quantityOrdered;
    }

    public function setQuantityOrdered(int $quantityOrdered): self
    {
        $this->quantityOrdered = $quantityOrdered;

        return $this;
    }

    #[Groups(['order:read'])]
    #[SerializedName('orderStatus')]
    public function getOrderStatus(): OrderStatus
    {
        return $this->orderStatus;
    }

    public function setOrderStatus(OrderStatus $orderStatus): self
    {
        $this->orderStatus = $orderStatus;

        return $this;
    }

    public function getOrderStatusValue(): string
    {
        return $this->orderStatus->value;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(?string $productId): self
    {
        $this->productId = $productId;

        return $this;
    }
}

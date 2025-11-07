<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\StateProcessor\ProductStateProcessor;
use Doctrine\ORM\Mapping as ORM;
use Microservices\SharedBundle\Entity\Product as BaseProduct;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['product:read']]),
        new Get(normalizationContext: ['groups' => ['product:read']]),
        new Post(
            denormalizationContext: ['groups' => ['product:write']],
            processor: ProductStateProcessor::class
        ),
        new Put(
            denormalizationContext: ['groups' => ['product:write']],
            processor: ProductStateProcessor::class
        ),
    ]
)]
class Product extends BaseProduct
{
    #[ApiProperty(identifier: true)]
    #[Groups(['product:read'])]
    #[SerializedName('id')]
    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): void
    {
        $this->id = $id;
    }

    #[Groups(['product:read', 'product:write'])]
    public function getName(): ?string
    {
        return parent::getName();
    }

    #[Groups(['product:read', 'product:write'])]
    public function setName(string $name): self
    {
        return parent::setName($name);
    }

    #[Groups(['product:read', 'product:write'])]
    public function getPrice(): ?float
    {
        return parent::getPrice();
    }

    #[Groups(['product:read', 'product:write'])]
    public function setPrice(float $price): self
    {
        return parent::setPrice($price);
    }

    #[Groups(['product:read', 'product:write'])]
    public function getQuantity(): ?int
    {
        return parent::getQuantity();
    }

    #[Groups(['product:read', 'product:write'])]
    public function setQuantity(int $quantity): self
    {
        return parent::setQuantity($quantity);
    }
}

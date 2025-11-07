<?php

declare(strict_types=1);

namespace Microservices\SharedBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\MappedSuperclass]
abstract class Product
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    protected Uuid $id;

    public function __construct(
        ?Uuid $id = null,
        #[ORM\Column(type: 'string', length: 255)]
        protected ?string $name = null,
        #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
        protected ?float $price = null,
        #[ORM\Column(type: 'integer')]
        protected ?int $quantity = null
    ) {
        $this->id = $id ?? Uuid::v7();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }
}

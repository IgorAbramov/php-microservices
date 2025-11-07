<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Microservices\SharedBundle\Entity\Product as BaseProduct;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
class Product extends BaseProduct
{
    #[Groups(['order:read'])]
    public function getId(): ?Uuid
    {
        return $this->id;
    }

    #[Groups(['order:read'])]
    public function getName(): ?string
    {
        return parent::getName();
    }

    #[Groups(['order:read'])]
    public function getPrice(): ?float
    {
        return parent::getPrice();
    }

    #[Groups(['order:read'])]
    public function getQuantity(): ?int
    {
        return parent::getQuantity();
    }
}

<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'good_parts')]
class GoodPart
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Good::class, inversedBy: 'parts')]
    #[ORM\JoinColumn(nullable: false)]
    private $good;

    #[ORM\ManyToOne(targetEntity: ProductPart::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $productPart;

    #[ORM\Column(type: 'integer', nullable: false)]
    private $quantity;

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'good_id' => $this->getGood()->getId(),
            'product_part' => $this->getProductPart()->toArray(),
            'quantity' => $this->getQuantity(),
        ];
    }

    // Геттеры и сеттеры
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGood(): ?Good
    {
        return $this->good;
    }

    public function setGood(?Good $good): self
    {
        $this->good = $good;
        return $this;
    }

    public function getProductPart(): ?ProductPart
    {
        return $this->productPart;
    }

    public function setProductPart(ProductPart $productPart): self
    {
        $this->productPart = $productPart;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }
} 
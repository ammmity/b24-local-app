<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'operation_prices')]
#[ORM\UniqueConstraint(name: "unique_operation_detail", columns: ["operation_type_id", "product_part_id"])]
class OperationPrice
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: OperationType::class)]
    #[ORM\JoinColumn(name: "operation_type_id", nullable: false)]
    private OperationType $operationType;

    #[ORM\ManyToOne(targetEntity: ProductPart::class)]
    #[ORM\JoinColumn(name: "product_part_id", nullable: true)]
    private ?ProductPart $productPart = null;

    #[ORM\Column(type: 'integer')]
    private int $price;

    public function __construct(
        OperationType $operationType,
        int $price,
        ?ProductPart $productPart = null
    ) {
        $this->operationType = $operationType;
        $this->price = $price;
        $this->productPart = $productPart;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOperationType(): OperationType
    {
        return $this->operationType;
    }

    public function setOperationType(OperationType $operationType): self
    {
        $this->operationType = $operationType;
        return $this;
    }

    public function getProductPart(): ?ProductPart
    {
        return $this->productPart;
    }

    public function setProductPart(?ProductPart $productPart): self
    {
        $this->productPart = $productPart;
        return $this;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'operation_type_id' => $this->operationType->getId(),
            'product_part_id' => $this->productPart?->getId(),
            'detail_name' => $this->productPart?->getName(),
            'price' => $this->price,
            'operation_type' => $this->operationType->toArray()
        ];
    }
} 
<?php

namespace App\Entities;

use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity, Table(name: 'product_production_stages')]
final class ProductProductionStage
{
    #[Id, Column(type: 'integer'), GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    #[ManyToOne(targetEntity: ProductPart::class)]
    #[JoinColumn(name: 'product_part_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private $productPart;

    #[ManyToOne(targetEntity: OperationType::class)]
    #[JoinColumn(name: 'operation_type_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private $operationType;

    #[Column(type: 'integer', nullable: false)]
    private $stage;

    #[Column(type: 'datetime', nullable: false)]
    private $created;

    #[Column(name: '`order`', type: 'integer', nullable: false)]
    private $order;

    public function __construct(ProductPart $productPart, OperationType $operationType, int $stage, int $order)
    {
        $this->productPart = $productPart;
        $this->operationType = $operationType;
        $this->stage = $stage;
        $this->order = $order;
        $this->created = new DateTime();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'product_part_id' => $this->getProductPart()->getId(),
            'operation_type_id' => $this->getOperationType()->getId(),
            'stage' => $this->getStage(),
            'created' => $this->getCreated()->format('Y-m-d H:i:s'),
            'order' => $this->getOrder()
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductPart(): ProductPart
    {
        return $this->productPart;
    }

    public function setProductPart(ProductPart $productPart): self
    {
        $this->productPart = $productPart;
        return $this;
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

    public function getStage(): int
    {
        return $this->stage;
    }

    public function setStage(int $stage): self
    {
        $this->stage = $stage;
        return $this;
    }

    public function getCreated(): DateTime
    {
        return $this->created;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): self
    {
        $this->order = $order;
        return $this;
    }
} 
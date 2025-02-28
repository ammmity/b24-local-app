<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'production_scheme_stages')]
class ProductionSchemeStage
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ProductionScheme::class, inversedBy: 'stages')]
    #[ORM\JoinColumn(nullable: false)]
    private ProductionScheme $scheme;

    #[ORM\ManyToOne(targetEntity: ProductPart::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ProductPart $productPart;

    #[ORM\ManyToOne(targetEntity: OperationType::class)]
    #[ORM\JoinColumn(nullable: false)]
    private OperationType $operationType;

    #[ORM\Column(type: 'integer')]
    private int $stageNumber;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $executorId = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $bitrixTaskId = null;

    public function __construct(
        ProductionScheme $scheme,
        ProductPart $productPart,
        OperationType $operationType,
        int $stageNumber,
        int $quantity
    ) {
        $this->scheme = $scheme;
        $this->productPart = $productPart;
        $this->operationType = $operationType;
        $this->stageNumber = $stageNumber;
        $this->quantity = $quantity;
        $this->status = 'new';
        $this->executorId = null;
        $this->bitrixTaskId = null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'scheme_id' => $this->scheme->getId(),
            'product_part_id' => $this->productPart->getId(),
            'operation_type_id' => $this->operationType->getId(),
            'stage_number' => $this->stageNumber,
            'quantity' => $this->quantity,
            'executor_id' => $this->executorId,
            'status' => $this->status,
            'bitrix_task_id' => $this->bitrixTaskId,
            'product_part' => $this->productPart->toArray(),
            'operation_type' => $this->operationType->toArray()
        ];
    }

    public function getBitrixTaskId(): ?int
    {
        return $this->bitrixTaskId;
    }

    public function setBitrixTaskId(?int $bitrixTaskId): self
    {
        $this->bitrixTaskId = $bitrixTaskId;
        return $this;
    }

    public function getScheme(): ProductionScheme
    {
        return $this->scheme;
    }

    public function setScheme(?ProductionScheme $scheme): self
    {
        $this->scheme = $scheme;
        return $this;
    }

    public function getExecutorId(): ?int
    {
        return $this->executorId;
    }

    public function setExecutorId(?int $executorId): self
    {
        $this->executorId = $executorId;
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
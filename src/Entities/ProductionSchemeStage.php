<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'production_scheme_stages')]
class ProductionSchemeStage
{
    public const STATUS_B24_TASK_NOT_LINKED = 0; // не создана задача в б24
    public const STATUS_WAITING = 'В ожидании';
    public const STATUS_COMPLETED = 'Завершены';
    public const STATUS_IN_PROGRESS = 'В работе';
    public const STATUS_NO_MATERIALS = 'Нет сырья';

    public const STATUS_LABELS = [
        self::STATUS_B24_TASK_NOT_LINKED => '',
        self::STATUS_WAITING => 'В ожидании',
        self::STATUS_COMPLETED => 'Завершено',
        self::STATUS_IN_PROGRESS => 'В работе',
        self::STATUS_NO_MATERIALS => 'Нет сырья'
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ProductionScheme::class, inversedBy: 'stages')]
    #[ORM\JoinColumn(nullable: false)]
    private ProductionScheme $scheme;

    #[ORM\ManyToOne(targetEntity: ProductPart::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?ProductPart $productPart = null;

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
        $this->status = self::STATUS_B24_TASK_NOT_LINKED;
        $this->executorId = null;
        $this->bitrixTaskId = null;
    }

    public function getStatusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'Неизвестный статус';
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
            'status_label' => $this->getStatusLabel(),
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

    public function getOperationType(): OperationType
    {
        return $this->operationType;
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

    public function getStageNumber(): int
    {
        return $this->stageNumber;
    }

    public function setStageNumber(int $stageNumber): self
    {
        $this->stageNumber = $stageNumber;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }
}

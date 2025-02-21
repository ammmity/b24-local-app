<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'product_production_stages')]
class ProductProductionStage
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $stage;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $created;

    #[ORM\ManyToOne(targetEntity: ProductPart::class)]
    #[ORM\JoinColumn(name: 'product_part_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ProductPart $productPart;

    #[ORM\ManyToOne(targetEntity: OperationType::class)]
    #[ORM\JoinColumn(name: 'operation_type_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private OperationType $operationType;

    public function __construct(ProductPart $productPart, OperationType $operationType, int $stage)
    {
        $this->productPart = $productPart;
        $this->operationType = $operationType;
        $this->stage = $stage;
        $this->created = new \DateTime();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'product_part_id' => $this->getProductPart()->getId(),
            'operation_type_id' => $this->getOperationType()->getId(),
            'operation_type' => [
                'name' => $this->getOperationType()->getName(),
                'machine' => $this->getOperationType()->getMachine()
            ],
            'stage' => $this->getStage(),
            'created' => $this->getCreated()->format('Y-m-d H:i:s')
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCreated(): \DateTime
    {
        return $this->created;
    }

    public function setCreated(\DateTime $created): self
    {
        $this->created = $created;
        return $this;
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
}

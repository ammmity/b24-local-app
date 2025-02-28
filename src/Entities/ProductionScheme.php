<?php

namespace App\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'production_schemes')]
class ProductionScheme
{
    public const STATUS_PREPARE = 'prepare';
    public const STATUS_PROGRESS = 'progress';
    public const STATUS_DONE = 'done';

    public const TYPE_STANDARD = 'standard';
    public const TYPE_IMPORTANT = 'important';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $dealId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status = self::STATUS_PREPARE;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type = self::TYPE_STANDARD;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\OneToMany(targetEntity: ProductionSchemeStage::class, mappedBy: 'scheme', cascade: ['persist', 'remove'])]
    private Collection $stages;

    public function __construct(int $dealId, string $type = self::TYPE_STANDARD)
    {
        $this->dealId = $dealId;
        $this->type = $type;
        $this->createdAt = new \DateTime();
        $this->stages = new ArrayCollection();
    }

    public function addStage(ProductionSchemeStage $stage): self
    {
        if (!$this->stages->contains($stage)) {
            $this->stages->add($stage);
            $stage->setScheme($this);
        }

        return $this;
    }

    public function removeStage(ProductionSchemeStage $stage): self
    {
        if ($this->stages->removeElement($stage)) {
            // установить owning side в null (если не изменено)
            if ($stage->getScheme() === $this) {
                $stage->setScheme(null);
            }
        }

        return $this;
    }

    public function getStages(): Collection
    {
        return $this->stages;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDealId(): int
    {
        return $this->dealId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, [self::STATUS_PREPARE, self::STATUS_PROGRESS, self::STATUS_DONE])) {
            throw new \InvalidArgumentException('Invalid status');
        }
        $this->status = $status;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        if (!in_array($type, [self::TYPE_STANDARD, self::TYPE_IMPORTANT])) {
            throw new \InvalidArgumentException('Invalid type');
        }
        $this->type = $type;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'deal_id' => $this->dealId,
            'status' => $this->status,
            'type' => $this->type,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'stages' => $this->stages->map(fn($stage) => $stage->toArray())->toArray()
        ];
    }
} 
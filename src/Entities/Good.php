<?php

namespace App\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'goods')]
class Good
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\Column(type: 'string', unique: false, nullable: false)]
    private $name;

    #[ORM\Column(type: 'string', unique: true, nullable: false)]
    private $xml_id;

    #[ORM\Column(type: 'integer', unique: false, nullable: false)]
    private $bitrix_id;

    #[ORM\OneToMany(targetEntity: GoodPart::class, mappedBy: 'good', cascade: ['persist', 'remove'])]
    private Collection $parts;

    public function __construct()
    {
        $this->parts = new ArrayCollection();
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'xml_id' => $this->getXmlId(),
            'bitrix_id' => $this->getBitrixId(),
        ];

        if (!$this->parts->isEmpty()) {
            $data['parts'] = $this->parts
                ->map(fn(GoodPart $part) => $part->toArray())
                ->toArray();
        }

        return $data;
    }

    // Геттеры и сеттеры
    public function getId(): ?int
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

    public function getXmlId(): ?string
    {
        return $this->xml_id;
    }

    public function setXmlId(string $xml_id): self
    {
        $this->xml_id = $xml_id;
        return $this;
    }

    public function getBitrixId(): ?int
    {
        return $this->bitrix_id;
    }

    public function setBitrixId(int $bitrix_id): self
    {
        $this->bitrix_id = $bitrix_id;
        return $this;
    }

    public function getParts(): Collection
    {
        return $this->parts;
    }

    public function addPart(GoodPart $part): self
    {
        if (!$this->parts->contains($part)) {
            $this->parts->add($part);
            $part->setGood($this);
        }
        return $this;
    }

    public function removePart(GoodPart $part): self
    {
        if ($this->parts->removeElement($part)) {
            if ($part->getGood() === $this) {
                $part->setGood(null);
            }
        }
        return $this;
    }
} 
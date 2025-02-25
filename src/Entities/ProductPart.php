<?php

namespace App\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\OneToMany;

#[Entity, Table(name: 'product_parts')]
class ProductPart
{
    #[Id, Column(type: 'integer'), GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[Column(type: 'string', unique: false, nullable: false)]
    private $name;

    #[Column(type: 'string', unique: true, nullable: false)]
    private $xml_id;

    #[Column(type: 'integer', unique: false, nullable: false)]
    private $bitrix_id;

    #[OneToMany(targetEntity: ProductProductionStage::class, mappedBy: 'productPart')]
    private Collection $productionStages;

    public function toArray(): array
    {
        $data = [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'xml_id' => $this->getXmlId(),
            'bitrix_id' => $this->getBitrixId(),
        ];

        // Добавляем этапы производства, если они есть
        if (!$this->productionStages->isEmpty()) {
            $data['production_stages'] = $this->productionStages
                ->map(fn(ProductProductionStage $stage) => $stage->toArray())
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

    /**
     * @return Collection<int, ProductProductionStage>
     */
    public function getProductionStages(): Collection
    {
        return $this->productionStages;
    }

    public function addProductionStage(ProductProductionStage $stage): self
    {
        if (!$this->productionStages->contains($stage)) {
            $this->productionStages->add($stage);
            $stage->setProductPart($this);
        }

        return $this;
    }

    public function removeProductionStage(ProductProductionStage $stage): self
    {
        if ($this->productionStages->removeElement($stage)) {
            // set the owning side to null (unless already changed)
            if ($stage->getProductPart() === $this) {
                $stage->setProductPart(null);
            }
        }

        return $this;
    }
}

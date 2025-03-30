<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity, Table(name: 'virtual_parts')]
class VirtualPart
{
    #[Id, Column(type: 'integer'), GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[Column(type: 'string', unique: false, nullable: false)]
    private $name;

    #[Column(type: 'integer', unique: true, nullable: false)]
    private $bitrix_id;

    public function __construct()
    {}

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'bitrix_id' => $this->getBitrixId(),
        ];
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

    public function getBitrixId(): ?int
    {
        return $this->bitrix_id;
    }

    public function setBitrixId(int $bitrix_id): self
    {
        $this->bitrix_id = $bitrix_id;
        return $this;
    }
}

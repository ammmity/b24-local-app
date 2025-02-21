<?php

namespace App\Entities;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity, Table(name: 'product_operation_types')]
final class OperationType
{
    #[Id, Column(type: 'integer'), GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[Column(type: 'string', unique: true, nullable: false)]
    private $name;

    #[Column(type: 'string', unique: true, nullable: false)]
    private $machine;

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'machine' => $this->getMachine(),
        ];
    }

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

    public function getMachine(): ?string
    {
        return $this->machine;
    }

    public function setMachine(string $machine): self
    {
        $this->machine = $machine;
        return $this;
    }
}

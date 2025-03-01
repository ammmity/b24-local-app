<?php

namespace App\Entities;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity, Table(name: 'product_operation_types')]
class OperationType
{
    #[Id, Column(type: 'integer'), GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[Column(type: 'string', unique: true, nullable: false)]
    private $name;

    #[Column(type: 'string', unique: true, nullable: false)]
    private $machine;

    #[Column(type: 'string', nullable: false)]
    private string $bitrix_group_id;

    public function __construct(string $name, string $machine, string $bitrix_group_id)
    {
        $this->setName($name);
        $this->setMachine($machine);
        $this->setBitrixGroupId($bitrix_group_id);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'machine' => $this->getMachine(),
            'bitrix_group_id' => $this->getBitrixGroupId(),
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

    public function getBitrixGroupId(): string
    {
        return $this->bitrix_group_id;
    }

    public function setBitrixGroupId(string $bitrix_group_id): self
    {
        $this->bitrix_group_id = $bitrix_group_id;
        return $this;
    }
}

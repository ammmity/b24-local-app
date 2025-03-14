<?php

namespace App\Entities;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity, Table(name: 'bitrix_group_kanban_stages')]
class BitrixGroupKanbanStage
{
    #[Id, Column(type: 'integer'), GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[Column(type: 'string', unique: false, nullable: false)]
    private string $bitrix_group_id;

    #[Column(type: 'string', unique: false, nullable: false)]
    private string $stage_id;

    #[Column(type: 'string', unique: false, nullable: false)]
    private string $stage_name;

    public function __construct(
        string $bitrix_group_id,
        string $stage_id,
        string $stage_name
    ) {
        $this->bitrix_group_id = $bitrix_group_id;
        $this->stage_id = $stage_id;
        $this->stage_name = $stage_name;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getStageId(): string
    {
        return $this->stage_id;
    }

    public function setStageId(string $stage_id): self
    {
        $this->stage_id = $stage_id;
        return $this;
    }

    public function getStageName(): string
    {
        return $this->stage_name;
    }

    public function setStageName(string $stage_name): self
    {
        $this->stage_name = $stage_name;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'bitrix_group_id' => $this->bitrix_group_id,
            'stage_id' => $this->stage_id,
            'stage_name' => $this->stage_name
        ];
    }
} 
<?php

namespace App\Entities;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'operation_logs')]
class OperationLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private string $taskLink;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $bitrixTaskId;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $dealId;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private DateTime $createdDate;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $detailId;

    #[ORM\Column(type: 'string', nullable: false)]
    private string $detailName;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $quantity;

    #[ORM\Column(name: '`username`', type: 'string', nullable: false)]
    private string $username;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $userId;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $price;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $sum = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private string $operation;

    public function __construct(
        string $taskLink,
        int $bitrixTaskId,
        int $dealId,
        int $detailId,
        string $detailName,
        int $quantity,
        string $username,
        int $userId,
        int $price,
        string $operation
    ) {
        $this->taskLink = $taskLink;
        $this->bitrixTaskId = $bitrixTaskId;
        $this->dealId = $dealId;
        $this->createdDate = new DateTime();
        $this->detailId = $detailId;
        $this->detailName = $detailName;
        $this->quantity = $quantity;
        $this->username = $username;
        $this->userId = $userId;
        $this->price = $price;
        $this->operation = $operation;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTaskLink(): string
    {
        return $this->taskLink;
    }

    public function setTaskLink(string $taskLink): self
    {
        $this->taskLink = $taskLink;
        return $this;
    }

    public function getBitrixTaskId(): int
    {
        return $this->bitrixTaskId;
    }

    public function setBitrixTaskId(int $bitrixTaskId): self
    {
        $this->bitrixTaskId = $bitrixTaskId;
        return $this;
    }

    public function getDealId(): int
    {
        return $this->dealId;
    }

    public function setDealId(int $dealId): self
    {
        $this->dealId = $dealId;
        return $this;
    }

    public function getCreatedDate(): DateTime
    {
        return $this->createdDate;
    }

    public function setCreatedDate(DateTime $createdDate): self
    {
        $this->createdDate = $createdDate;
        return $this;
    }

    public function getDetailId(): int
    {
        return $this->detailId;
    }

    public function setDetailId(int $detailId): self
    {
        $this->detailId = $detailId;
        return $this;
    }

    public function getDetailName(): string
    {
        return $this->detailName;
    }

    public function setDetailName(string $detailName): self
    {
        $this->detailName = $detailName;
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

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getSum(): ?int
    {
        return $this->sum;
    }

    public function setSum(?int $sum): self
    {
        $this->sum = $sum;
        return $this;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function setOperation(string $operation): self
    {
        $this->operation = $operation;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'task_link' => $this->taskLink,
            'bitrix_task_id' => $this->bitrixTaskId,
            'deal_id' => $this->dealId,
            'created_date' => $this->createdDate->format('Y-m-d H:i:s'),
            'detail_id' => $this->detailId,
            'detail_name' => $this->detailName,
            'quantity' => $this->quantity,
            'username' => $this->username,
            'user_id' => $this->userId,
            'price' => $this->price,
            'sum' => $this->price * $this->quantity,
            'operation' => $this->operation,
        ];
    }
}

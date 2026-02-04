<?php

namespace App\Entity;

use App\Repository\ReportBySkuRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportBySkuRepository::class)]
class ReportBySku
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sku_name = null;

    #[ORM\Column(nullable: true)]
    private ?int $quantity = null;

    #[ORM\Column(nullable: true)]
    private ?int $week_number = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $channel = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created_date = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_date = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSkuName(): ?string
    {
        return $this->sku_name;
    }

    public function setSkuName(?string $sku_name): static
    {
        $this->sku_name = $sku_name;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getWeekNumber(): ?int
    {
        return $this->week_number;
    }

    public function setWeekNumber(?int $week_number): static
    {
        $this->week_number = $week_number;

        return $this;
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    public function setChannel(?string $channel): static
    {
        $this->channel = $channel;

        return $this;
    }

    public function getCreatedDate(): ?\DateTimeInterface
    {
        return $this->created_date;
    }

    public function setCreatedDate(?\DateTimeInterface $created_date): static
    {
        $this->created_date = $created_date;

        return $this;
    }

    public function getUpdatedDate(): ?\DateTimeInterface
    {
        return $this->updated_date;
    }

    public function setUpdatedDate(?\DateTimeInterface $updated_date): static
    {
        $this->updated_date = $updated_date;

        return $this;
    }
}
<?php

namespace App\Entity;

use App\Repository\CampaignConfigRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CampaignConfigRepository::class)]
class CampaignConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $week_number = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $start_date = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $end_date = null;

    #[ORM\Column(nullable: true)]
    private ?int $sku_1_limit = null;

    #[ORM\Column(nullable: true)]
    private ?int $sku_2_limit = null;

    #[ORM\Column(nullable: true)]
    private ?int $sku_3_limit = null;

    #[ORM\Column(nullable: true)]
    private ?int $sku_4_limit = null;

    #[ORM\Column(nullable: true)]
    private ?int $sku_5_limit = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->start_date;
    }

    public function setStartDate(?\DateTimeInterface $start_date): static
    {
        $this->start_date = $start_date;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->end_date;
    }

    public function setEndDate(?\DateTimeInterface $end_date): static
    {
        $this->end_date = $end_date;

        return $this;
    }

    public function getSku1Limit(): ?int
    {
        return $this->sku_1_limit;
    }

    public function setSku1Limit(?int $sku_1_limit): static
    {
        $this->sku_1_limit = $sku_1_limit;

        return $this;
    }

    public function getSku2Limit(): ?int
    {
        return $this->sku_2_limit;
    }

    public function setSku2Limit(?int $sku_2_limit): static
    {
        $this->sku_2_limit = $sku_2_limit;

        return $this;
    }

    public function getSku3Limit(): ?int
    {
        return $this->sku_3_limit;
    }

    public function setSku3Limit(?int $sku_3_limit): static
    {
        $this->sku_3_limit = $sku_3_limit;

        return $this;
    }

    public function getSku4Limit(): ?int
    {
        return $this->sku_4_limit;
    }

    public function setSku4Limit(?int $sku_4_limit): static
    {
        $this->sku_4_limit = $sku_4_limit;

        return $this;
    }

    public function getSku5Limit(): ?int
    {
        return $this->sku_5_limit;
    }

    public function setSku5Limit(?int $sku_5_limit): static
    {
        $this->sku_5_limit = $sku_5_limit;

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\GwpTrxRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GwpTrxRepository::class)]
class GwpTrx
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $is_completed = null;

    #[ORM\Column(nullable: true)]
    private ?int $sub_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $field7 = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $completed_date = null;

    #[ORM\Column(nullable: true)]
    private ?int $seq = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $product_redeem = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    private ?int $diy_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $region = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function isCompleted(): ?bool
    {
        return $this->is_completed;
    }

    public function setCompleted(bool $is_completed): static
    {
        $this->is_completed = $is_completed;

        return $this;
    }

    public function getSubId(): ?int
    {
        return $this->sub_id;
    }

    public function setSubId(?int $sub_id): static
    {
        $this->sub_id = $sub_id;

        return $this;
    }

    public function getField7(): ?string
    {
        return $this->field7;
    }

    public function setField7(?string $field7): static
    {
        $this->field7 = $field7;

        return $this;
    }

    public function getCompletedDate(): ?\DateTimeInterface
    {
        return $this->completed_date;
    }

    public function setCompletedDate(?\DateTimeInterface $completed_date): static
    {
        $this->completed_date = $completed_date;

        return $this;
    }

    public function getSeq(): ?int
    {
        return $this->seq;
    }

    public function setSeq(?int $seq): static
    {
        $this->seq = $seq;

        return $this;
    }

    public function getProductRedeem(): ?string
    {
        return $this->product_redeem;
    }

    public function setProductRedeem(?string $product_redeem): static
    {
        $this->product_redeem = $product_redeem;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getDiyId(): ?int
    {
        return $this->diy_id;
    }

    public function setDiyId(?int $diy_id): static
    {
        $this->diy_id = $diy_id;

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): static
    {
        $this->region = $region;

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\PrismTableRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrismTableRepository::class)]
class PrismTable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 350, nullable: true)]
    private ?string $full_name = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $mobile_no = null;

    #[ORM\Column(length: 350, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $national_id = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $receipt_no = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $attachment = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $is_completed = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $completed_date = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $created_date = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullName(): ?string
    {
        return $this->full_name;
    }

    public function setFullName(?string $full_name): static
    {
        $this->full_name = $full_name;

        return $this;
    }

    public function getMobileNo(): ?string
    {
        return $this->mobile_no;
    }

    public function setMobileNo(?string $mobile_no): static
    {
        $this->mobile_no = $mobile_no;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getNationalId(): ?string
    {
        return $this->national_id;
    }

    public function setNationalId(?string $national_id): static
    {
        $this->national_id = $national_id;

        return $this;
    }

    public function getReceiptNo(): ?string
    {
        return $this->receipt_no;
    }

    public function setReceiptNo(?string $receipt_no): static
    {
        $this->receipt_no = $receipt_no;

        return $this;
    }

    public function getAttachment(): ?string
    {
        return $this->attachment;
    }

    public function setAttachment(?string $attachment): static
    {
        $this->attachment = $attachment;

        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->is_completed;
    }

    public function setCompleted(bool $is_completed): static
    {
        $this->is_completed = $is_completed;

        return $this;
    }

    public function getCompletedDate(): ?\DateTime
    {
        return $this->completed_date;
    }

    public function setCompletedDate(?\DateTime $completed_date): static
    {
        $this->completed_date = $completed_date;

        return $this;
    }

    public function getCreatedDate(): ?\DateTime
    {
        return $this->created_date;
    }

    public function setCreatedDate(?\DateTime $created_date): static
    {
        $this->created_date = $created_date;

        return $this;
    }
}
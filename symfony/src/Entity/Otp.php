<?php

namespace App\Entity;

use App\Repository\OtpRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OtpRepository::class)]
class Otp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $code = null;

    #[ORM\Column(length: 25, nullable: true)]
    private ?string $phone_no = null;

    #[ORM\Column]
    private ?bool $is_valid = null;

    #[ORM\ManyToOne(inversedBy: 'otps')]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created_date = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?int
    {
        return $this->code;
    }

    public function setCode(?int $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getPhoneNo(): ?string
    {
        return $this->phone_no;
    }

    public function setPhoneNo(?string $phone_no): static
    {
        $this->phone_no = $phone_no;

        return $this;
    }

    public function isValid(): ?bool
    {
        return $this->is_valid;
    }

    public function setValid(bool $is_valid): static
    {
        $this->is_valid = $is_valid;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

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
}

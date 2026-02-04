<?php

namespace App\Entity;

use App\Repository\ProcessingStateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProcessingStateRepository::class)]
class ProcessingState
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $process_name = null;

    #[ORM\Column(length: 150)]
    private ?string $last_category = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updated_date = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProcessName(): ?string
    {
        return $this->process_name;
    }

    public function setProcessName(string $process_name): static
    {
        $this->process_name = $process_name;
        return $this;
    }

    public function getLastCategory(): ?string
    {
        return $this->last_category;
    }

    public function setLastCategory(string $last_category): static
    {
        $this->last_category = $last_category;
        return $this;
    }

    public function getUpdatedDate(): ?\DateTimeInterface
    {
        return $this->updated_date;
    }

    public function setUpdatedDate(\DateTimeInterface $updated_date): static
    {
        $this->updated_date = $updated_date;
        return $this;
    }
}
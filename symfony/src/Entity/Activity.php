<?php

namespace App\Entity;

use App\Repository\ActivityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
class Activity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $activity_name = null;

    #[ORM\Column(length: 300)]
    private ?string $context_field1 = null;

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $context_field2 = null;

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $context_field3 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $username = null;

    #[ORM\Column(length: 25, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $user_agent = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created_date = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getActivityName(): ?string
    {
        return $this->activity_name;
    }

    public function setActivityName(?string $activity_name): static
    {
        $this->activity_name = $activity_name;

        return $this;
    }

    public function getContextField1(): ?string
    {
        return $this->context_field1;
    }

    public function setContextField1(string $context_field1): static
    {
        $this->context_field1 = $context_field1;

        return $this;
    }

    public function getContextField2(): ?string
    {
        return $this->context_field2;
    }

    public function setContextField2(?string $context_field2): static
    {
        $this->context_field2 = $context_field2;

        return $this;
    }

    public function getContextField3(): ?string
    {
        return $this->context_field3;
    }

    public function setContextField3(?string $context_field3): static
    {
        $this->context_field3 = $context_field3;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): static
    {
        $this->ip = $ip;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->user_agent;
    }

    public function setUserAgent(?string $user_agent): static
    {
        if(count_chars($user_agent,3) >= 500){
            $user_agent = substr($user_agent, -500);
        }        
        $this->user_agent = $user_agent;
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

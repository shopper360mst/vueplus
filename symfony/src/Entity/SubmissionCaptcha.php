<?php

namespace App\Entity;

use App\Repository\SubmissionCaptchaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubmissionCaptchaRepository::class)]
class SubmissionCaptcha
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $ref_user_id = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $success_status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $challenge_ts = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $hostname = null;

    #[ORM\Column(nullable: true)]
    private ?float $score = null;

    #[ORM\Column(length: 50,nullable: true)]
    private ?string $action = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRefUserId(): ?int
    {
        return $this->ref_user_id;
    }

    public function setRefUserId(?int $ref_user_id): static
    {
        $this->ref_user_id = $ref_user_id;

        return $this;
    }

    public function getSuccessStatus(): ?string
    {
        return $this->success_status;
    }

    public function setSuccessStatus(?string $success_status): static
    {
        $this->success_status = $success_status;

        return $this;
    }

    public function getChallengeTs(): ?\DateTimeInterface
    {
        return $this->challenge_ts;
    }

    public function setChallengeTs(?\DateTimeInterface $challenge_ts): static
    {
        $this->challenge_ts = $challenge_ts;

        return $this;
    }

    public function getHostname(): ?string
    {
        return $this->hostname;
    }

    public function setHostname(?string $hostname): static
    {
        $this->hostname = $hostname;

        return $this;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(?float $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): static
    {
        $this->action = $action;

        return $this;
    }
}

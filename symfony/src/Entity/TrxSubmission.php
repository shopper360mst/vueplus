<?php

namespace App\Entity;

use App\Repository\TrxSubmissionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrxSubmissionRepository::class)]
class TrxSubmission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $full_name = null;

    #[ORM\Column(length: 350, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $mobile_no = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $national_id = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $attachment_no = null;

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $attachment = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created_date = null;

    #[ORM\Column(nullable: true)]
    private ?int $sub_id = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $sub_status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reject_reason = null;

    #[ORM\Column(nullable: true)]
    private ?int $diy_id = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $submit_type = null;

    #[ORM\Column]
    private ?bool $is_completed = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $completed_date = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $status1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reason1 = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $status2 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reason2 = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $status3 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reason3 = null;

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

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

    public function getNationalId(): ?string
    {
        return $this->national_id;
    }

    public function setNationalId(?string $national_id): static
    {
        $this->national_id = $national_id;

        return $this;
    }

    public function getAttachmentNo(): ?string
    {
        return $this->attachment_no;
    }

    public function setAttachmentNo(?string $attachment_no): static
    {
        $this->attachment_no = $attachment_no;

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

    public function getCreatedDate(): ?\DateTimeInterface
    {
        return $this->created_date;
    }

    public function setCreatedDate(?\DateTimeInterface $created_date): static
    {
        $this->created_date = $created_date;

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

    public function getSubStatus(): ?string
    {
        return $this->sub_status;
    }

    public function setSubStatus(?string $sub_status): static
    {
        $this->sub_status = $sub_status;

        return $this;
    }

    public function getRejectReason(): ?string
    {
        return $this->reject_reason;
    }

    public function setRejectReason(?string $reject_reason): static
    {
        $this->reject_reason = $reject_reason;

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

    public function getSubmitType(): ?string
    {
        return $this->submit_type;
    }

    public function setSubmitType(?string $submit_type): static
    {
        $this->submit_type = $submit_type;

        return $this;
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

    public function getCompletedDate(): ?\DateTimeInterface
    {
        return $this->completed_date;
    }

    public function setCompletedDate(?\DateTimeInterface $completed_date): static
    {
        $this->completed_date = $completed_date;

        return $this;
    }

    public function getStatus1(): ?string
    {
        return $this->status1;
    }

    public function setStatus1(?string $status1): static
    {
        $this->status1 = $status1;

        return $this;
    }

    public function getReason1(): ?string
    {
        return $this->reason1;
    }

    public function setReason1(?string $reason1): static
    {
        $this->reason1 = $reason1;

        return $this;
    }

    public function getStatus2(): ?string
    {
        return $this->status2;
    }

    public function setStatus2(?string $status2): static
    {
        $this->status2 = $status2;

        return $this;
    }

    public function getReason2(): ?string
    {
        return $this->reason2;
    }

    public function setReason2(?string $reason2): static
    {
        $this->reason2 = $reason2;

        return $this;
    }

    public function getStatus3(): ?string
    {
        return $this->status3;
    }

    public function setStatus3(?string $status3): static
    {
        $this->status3 = $status3;

        return $this;
    }

    public function getReason3(): ?string
    {
        return $this->reason3;
    }

    public function setReason3(?string $reason3): static
    {
        $this->reason3 = $reason3;

        return $this;
    }
}

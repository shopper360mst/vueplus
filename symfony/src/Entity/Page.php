<?php

namespace App\Entity;

use App\Repository\PageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PageRepository::class)]
class Page
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $page_title = null;

    #[ORM\Column(length: 255)]
    private ?string $url_code = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $page_content = null;

    #[ORM\Column(length: 15)]
    private ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $created_by = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created_date = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $updated_by = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_date = null;

    #[ORM\Column]
    private ?bool $is_archived = null;

  
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPageTitle(): ?string
    {
        return $this->page_title;
    }

    public function setPageTitle(?string $page_title): static
    {
        $this->page_title = $page_title;

        return $this;
    }

    public function getUrlCode(): ?string
    {
        return $this->url_code;
    }

    public function setUrlCode(string $url_code): static
    {
        $this->url_code = $url_code;

        return $this;
    }

    public function getPageContent(): ?string
    {
        return $this->page_content;
    }

    public function setPageContent(?string $page_content): static
    {
        $this->page_content = $page_content;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedBy(): ?string
    {
        return $this->created_by;
    }

    public function setCreatedBy(?string $created_by): static
    {
        $this->created_by = $created_by;

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

    public function getUpdatedBy(): ?string
    {
        return $this->updated_by;
    }

    public function setUpdatedBy(?string $updated_by): static
    {
        $this->updated_by = $updated_by;

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

    public function isArchived(): ?bool
    {
        return $this->is_archived;
    }

    public function setArchived(bool $is_archived): static
    {
        $this->is_archived = $is_archived;

        return $this;
    }

  
}

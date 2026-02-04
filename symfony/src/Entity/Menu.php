<?php

namespace App\Entity;

use App\Repository\MenuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MenuRepository::class)]
class Menu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $label = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $menu_code = null;
    
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $submenu_code = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    #[ORM\Column]
    private ?int $menu_index = null;

    #[ORM\Column]
    private ?int $weight = null;

    #[ORM\Column]
    private ?bool $is_published = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_popopen = null;

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $locale = null;


    public function __construct()
    {
    }

    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getMenuCode(): ?string
    {
        return $this->menu_code;
    }

    public function setMenuCode(?string $menu_code): static
    {
        $this->menu_code = $menu_code;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

   

    public function isPublished(): ?bool
    {
        return $this->is_published;
    }

    public function setPublished(bool $is_published): static
    {
        $this->is_published = $is_published;

        return $this;
    }

    public function getMenuIndex(): ?int
    {
        return $this->menu_index;
    }

    public function setMenuIndex(int $menu_index): static
    {
        $this->menu_index = $menu_index;

        return $this;
    }

    public function getSubmenuCode(): ?string
    {
        return $this->submenu_code;
    }

    public function setSubmenuCode(?string $submenu_code): static
    {
        $this->submenu_code = $submenu_code;

        return $this;
    }

    public function isPopopen(): ?bool
    {
        return $this->is_popopen;
    }

    public function setPopopen(?bool $is_popopen): static
    {
        $this->is_popopen = $is_popopen;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

  

    
}
<?php

namespace App\Entity;

use App\Repository\TngRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TngRepository::class)]
class Tng
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $product_code = null;

    #[ORM\Column(length: 255)]
    private ?string $tng_code = null;

    #[ORM\Column(length: 255)]
    private ?string $pin_code = null;

    #[ORM\Column(nullable: true)]
    private ?int $sub_id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created_date = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_date = null;

    #[ORM\Column]
    private ?bool $is_claimed = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $claimed_date = null;

    #[ORM\Column]
    private ?bool $is_locked = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $locked_date = null;

    public function encrypt($message) {
        $key = $_SERVER['SALT_KEY'];        
        $ivlen = openssl_cipher_iv_length("aes-256-cbc");
        $iv = substr($_SERVER['APP_SECRET'], 0, 16);
        $encrypted = openssl_encrypt($message, 'aes-256-cbc', $key, 0, $iv);
        return $encrypted;
    }

    public function decrypt($message) {
        $key = $_SERVER['SALT_KEY'];        
        $ivlen = openssl_cipher_iv_length("aes-256-cbc");
        $iv = substr($_SERVER['APP_SECRET'], 0, 16);
        $decrypted = openssl_decrypt($message, 'aes-256-cbc', $key, 0, $iv);
        return $decrypted;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductCode(): ?string
    {
        return $this->decrypt($this->product_code);
    }

    public function setProductCode(?string $product_code): static
    {
        if ($product_code !== null) {
            $this->product_code = $this->encrypt($product_code);
        } else {
            $this->product_code = null;
        }
        return $this;
    }

    public function getTngCode(): ?string
    {
        return $this->decrypt($this->tng_code);
    }

    public function setTngCode(?string $tng_code): static
    {
        if ($tng_code !== null) {
            $this->tng_code = $this->encrypt($tng_code);
        } else {
            $this->tng_code = null;
        }
        return $this;
    }

    public function getPinCode(): ?string
    {
        return $this->decrypt($this->pin_code);
    }

    public function setPinCode(?string $pin_code): static
    {
        if ($pin_code !== null) {
            $this->pin_code = $this->encrypt($pin_code);
        } else {
            $this->pin_code = null;
        }
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

    public function getCreatedDate(): ?\DateTimeInterface
    {
        return $this->created_date;
    }

    public function setCreatedDate(?\DateTimeInterface $created_date): static
    {
        $this->created_date = $created_date;
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

    public function isClaimed(): ?bool
    {
        return $this->is_claimed;
    }

    public function setIsClaimed(bool $is_claimed): static
    {
        $this->is_claimed = $is_claimed;
        return $this;
    }

    public function getClaimedDate(): ?\DateTimeInterface
    {
        return $this->claimed_date;
    }

    public function setClaimedDate(?\DateTimeInterface $claimed_date): static
    {
        $this->claimed_date = $claimed_date;
        return $this;
    }

    public function isLocked(): ?bool
    {
        return $this->is_locked;
    }

    public function setIsLocked(bool $is_locked): static
    {
        $this->is_locked = $is_locked;
        return $this;
    }

    public function getLockedDate(): ?\DateTimeInterface
    {
        return $this->locked_date;
    }

    public function setLockedDate(?\DateTimeInterface $locked_date): static
    {
        $this->locked_date = $locked_date;
        return $this;
    }
}
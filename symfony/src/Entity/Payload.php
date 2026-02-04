<?php

namespace App\Entity;

use App\Repository\PayloadRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PayloadRepository::class)]
class Payload
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created_date = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $payload = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $score = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $user_agent = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPayload(): ?string
    {
        $decrypt = $this->decrypt($this->payload);
        return $decrypt;
    }

    public function setPayload(?string $payload): static
    {
        $encrypt = $this->encrypt($payload);
        $this->payload = $encrypt;
        return $this;
    }

    public function getScore(): ?string
    {
        return $this->score;
    }

    public function setScore(?string $score): static
    {
        $this->score = $score;

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

     public function encrypt($message) {
        $key = $_SERVER['SALT_KEY'];        
        $ivlen = openssl_cipher_iv_length("aes-256-cbc");
        $iv = substr($_SERVER['APP_SECRET'],0,16);
        $encrypted = openssl_encrypt($message,'aes-256-cbc',$key,0,$iv);
        return $encrypted;
    }

	public function decrypt($message) {
        $key = $_SERVER['SALT_KEY'];        
        $ivlen = openssl_cipher_iv_length("aes-256-cbc");
        $iv = substr($_SERVER['APP_SECRET'],0,16);
        $decrypted = openssl_decrypt($message,'aes-256-cbc',$key,0,$iv);
        return $decrypted;
    }

    public function getUserAgent(): ?string
    {
        return $this->user_agent;
    }

    public function setUserAgent(?string $user_agent): static
    {
        $this->user_agent = $user_agent;

        return $this;
    }
}

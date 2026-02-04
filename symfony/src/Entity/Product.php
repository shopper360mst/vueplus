<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $product_code = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $product_category = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $product_type = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $product_name = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $product_sku = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expiry_date = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_locked = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $locked_date = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_collected = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $collected_date = null;

    #[ORM\Column(length: 350, nullable: true)]
    private ?string $receiver_full_name = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $receiver_mobile_no = null;

    #[ORM\Column(length: 350, nullable: true)]
    private ?string $address1 = null;

    #[ORM\Column(length: 350, nullable: true)]
    private ?string $address2 = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $postcode = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $state = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created_date = null;

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $product_photo = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $courier_details = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $delivery_status = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $courier_status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $delivered_date = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $details_updated_date = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_date = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_contacted = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?User $user = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_deleted = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $due_date = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $r_approved_date = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Submission $sub_id = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $return_remarks = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $region = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $delivery_assign = null;


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
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductCode(): ?string
    {
        return $this->product_code;
    }

    public function setProductCode(string $product_code): static
    {
        $this->product_code = $product_code;

        return $this;
    }

    public function getProductCategory(): ?string
    {
        return $this->product_category;
    }

    public function setProductCategory(?string $product_category): static
    {
        $this->product_category = $product_category;

        return $this;
    }

    public function getProductType(): ?string
    {
        return $this->product_type;
    }

    public function setProductType(?string $product_type): static
    {
        $this->product_type = $product_type;

        return $this;
    }

    public function getProductName(): ?string
    {
        return $this->product_name;
    }

    public function setProductName(?string $product_name): static
    {
        $this->product_name = $product_name;

        return $this;
    }

    public function getProductSku(): ?string
    {
        return $this->product_sku;
    }

    public function setProductSku(?string $product_sku): static
    {
        $this->product_sku = $product_sku;

        return $this;
    }

    public function getExpiryDate(): ?\DateTimeInterface
    {
        return $this->expiry_date;
    }

    public function setExpiryDate(?\DateTimeInterface $expiry_date): static
    {
        $this->expiry_date = $expiry_date;

        return $this;
    }

    public function isLocked(): ?bool
    {
        return $this->is_locked;
    }

    public function setLocked(bool $is_locked): static
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

    public function isCollected(): ?bool
    {
        return $this->is_collected;
    }

    public function setCollected(?bool $is_collected): static
    {
        $this->is_collected = $is_collected;

        return $this;
    }

    public function getCollectedDate(): ?\DateTimeInterface
    {
        return $this->collected_date;
    }

    public function setCollectedDate(?\DateTimeInterface $collected_date): static
    {
        $this->collected_date = $collected_date;

        return $this;
    }

    public function getReceiverFullName(): ?string
    {
        $decrypt = $this->decrypt($this->receiver_full_name);
        return $decrypt;
    }

    public function setReceiverFullName(?string $receiver_full_name): static
    {
        $encrypt = $this->encrypt($receiver_full_name);
        $this->receiver_full_name = $encrypt;
        return $this;        
    }

    public function getReceiverMobileNo(): ?string
    {
        $decrypt = $this->decrypt($this->receiver_mobile_no);
        return $decrypt;
    }

    public function setReceiverMobileNo(?string $receiver_mobile_no): static
    {
        $encrypt = $this->encrypt($receiver_mobile_no);
        $this->receiver_mobile_no = $encrypt;
        return $this;      
    }

    public function getAddress1(): ?string
    {
        $decrypt = $this->decrypt($this->address1);
        return $decrypt;
    }

    public function setAddress1(?string $address1): static
    {
        $encrypt = $this->encrypt($address1);
        $this->address1 = $encrypt;
        return $this;
    }

    public function getAddress2(): ?string
    {
        $decrypt = $this->decrypt($this->address2);
        return $decrypt;
    }

    public function setAddress2(?string $address2): static
    {
        $encrypt = $this->encrypt($address2);
        $this->address2 = $encrypt;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    public function setPostcode(?string $postcode): static
    {
        $this->postcode = $postcode;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): static
    {
        $this->state = $state;

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

    public function getProductPhoto(): ?string
    {
        return $this->product_photo;
    }

    public function setProductPhoto(?string $product_photo): static
    {
        $this->product_photo = $product_photo;

        return $this;
    }

    public function getCourierDetails(): ?string
    {
        return $this->courier_details;
    }

    public function setCourierDetails(?string $courier_details): static
    {
        $this->courier_details = $courier_details;

        return $this;
    }

    public function getDeliveryStatus(): ?string
    {
        return $this->delivery_status;
    }

    public function setDeliveryStatus(?string $delivery_status): static
    {
        $this->delivery_status = $delivery_status;

        return $this;
    }

    public function getCourierStatus(): ?string
    {
        return $this->courier_status;
    }

    public function setCourierStatus(?string $courier_status): static
    {
        $this->courier_status = $courier_status;

        return $this;
    }

    public function getDeliveredDate(): ?\DateTimeInterface
    {
        return $this->delivered_date;
    }

    public function setDeliveredDate(?\DateTimeInterface $delivered_date): static
    {
        $this->delivered_date = $delivered_date;

        return $this;
    }

    public function getDetailsUpdatedDate(): ?\DateTimeInterface
    {
        return $this->details_updated_date;
    }

    public function setDetailsUpdatedDate(?\DateTimeInterface $details_updated_date): static
    {
        $this->details_updated_date = $details_updated_date;

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

    public function isContacted(): ?bool
    {
        return $this->is_contacted;
    }

    public function setContacted(?bool $is_contacted): static
    {
        $this->is_contacted = $is_contacted;

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

    public function isDeleted(): ?bool
    {
        return $this->is_deleted;
    }

    public function setDeleted(?bool $is_deleted): static
    {
        $this->is_deleted = $is_deleted;

        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->due_date;
    }

    public function setDueDate(?\DateTimeInterface $due_date): static
    {
        $this->due_date = $due_date;

        return $this;
    }

    public function getRApprovedDate(): ?\DateTimeInterface
    {
        return $this->r_approved_date;
    }

    public function setRApprovedDate(?\DateTimeInterface $r_approved_date): static
    {
        $this->r_approved_date = $r_approved_date;

        return $this;
    }

    public function getSubId(): ?Submission
    {
        return $this->sub_id;
    }

    public function setSubId(?Submission $sub_id): static
    {
        $this->sub_id = $sub_id;

        return $this;
    }

    public function getReturnRemarks(): ?string
    {
        return $this->return_remarks;
    }

    public function setReturnRemarks(?string $return_remarks): static
    {
        $this->return_remarks = $return_remarks;

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): static
    {
        $this->region = $region;

        return $this;
    }

    public function getDeliveryAssign(): ?string
    {
        return $this->delivery_assign;
    }

    public function setDeliveryAssign(?string $delivery_assign): static
    {
        $this->delivery_assign = $delivery_assign;

        return $this;
    }
}

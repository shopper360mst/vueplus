<?php

namespace App\Entity;

use App\Repository\SubmissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SubmissionRepository::class)]
class Submission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $submit_code = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $submit_type = null;

    #[ORM\Column(length: 350, nullable: true)]
    private ?string $receiver_full_name = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $receiver_mobile_no = null;

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $full_name = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $mobile_no = null;
    
    #[ORM\Column(length: 350, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $national_id = null;

    #[ORM\Column(length: 350, nullable: true)]
    private ?string $address_1 = null;

    #[ORM\Column(length: 350, nullable: true)]
    private ?string $address_2 = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $postcode = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $state = null;

    #[ORM\Column(length: 6, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $reject_reason = null;

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $attachment = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $attachment_no = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $field1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $field2 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $field3 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $field4 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $field5 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $field6 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $field7 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $field8 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $field9 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $field10 = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created_date = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $product_ref = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $r_checked_date = null;

    #[ORM\ManyToOne(inversedBy: 'submissions')]
    private ?User $user = null;

    // Validation properties for encrypted fields (not persisted to database)
    #[Assert\NotBlank(message: "Receiver full name is required")]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: "Receiver full name must be at least {{ limit }} characters long",
        maxMessage: "Receiver full name cannot be longer than {{ limit }} characters"
    )]
    private ?string $receiverFullNamePlain = null;

    #[Assert\NotBlank(message: "Receiver mobile number is required")]
    #[Assert\Regex(
        pattern: "/^(\+?6?01)[0-46-9]-*[0-9]{7,8}$/",
        message: "Please enter a valid Malaysian mobile number"
    )]
    private ?string $receiverMobileNoPlain = null;

    #[Assert\NotBlank(message: "Full name is required")]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: "Full name must be at least {{ limit }} characters long",
        maxMessage: "Full name cannot be longer than {{ limit }} characters"
    )]
    private ?string $fullNamePlain = null;

    #[Assert\NotBlank(message: "Mobile number is required")]
    #[Assert\Regex(
        pattern: "/^(\+?6?01)[0-46-9]-*[0-9]{7,8}$/",
        message: "Please enter a valid Malaysian mobile number"
    )]
    private ?string $mobileNoPlain = null;

    #[Assert\NotBlank(message: "Email is required")]
    #[Assert\Email(message: "Please enter a valid email address")]
    #[Assert\Length(
        max: 100,
        maxMessage: "Email cannot be longer than {{ limit }} characters"
    )]
    private ?string $emailPlain = null;

    #[Assert\NotBlank(message: "National ID is required")]
    #[Assert\Regex(
        pattern: "/^[0-9]{6}-[0-9]{2}-[0-9]{4}$|^[0-9]{12}$/",
        message: "Please enter a valid Malaysian IC number (format: 123456-12-1234 or 123456121234)"
    )]
    private ?string $nationalIdPlain = null;

    #[Assert\NotBlank(message: "Address is required")]
    #[Assert\Length(
        min: 5,
        max: 200,
        minMessage: "Address must be at least {{ limit }} characters long",
        maxMessage: "Address cannot be longer than {{ limit }} characters"
    )]
    private ?string $address1Plain = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_date = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $s_validate_date = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $r_status = null;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'sub_id')]
    private Collection $products;

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $attachment2 = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $status1 = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $status2 = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $status3 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reason1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reason2 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reason3 = null;

    public function __construct()
    {
        $this->products = new ArrayCollection();
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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubmitCode(): ?string
    {
        return $this->submit_code;
    }

    public function setSubmitCode(string $submit_code): static
    {
        $this->submit_code = $submit_code;

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

    public function getReceiverFullName(): ?string
    {
        $decrypt = $this->decrypt($this->receiver_full_name);
        return $decrypt;
    }

    public function setReceiverFullName(?string $receiver_full_name): static
    {
        $this->receiverFullNamePlain = $receiver_full_name;
        if ($receiver_full_name !== null) {
            $encrypt = $this->encrypt($receiver_full_name);
            $this->receiver_full_name = $encrypt;
        } else {
            $this->receiver_full_name = null;
        }
        return $this;
    }

    public function getReceiverMobileNo(): ?string
    {
        $decrypt = $this->decrypt($this->receiver_mobile_no);
        return $decrypt;
    }

    public function setReceiverMobileNo(?string $receiver_mobile_no): static
    {
        $this->receiverMobileNoPlain = $receiver_mobile_no;
        if ($receiver_mobile_no !== null) {
            $encrypt = $this->encrypt($receiver_mobile_no);
            $this->receiver_mobile_no = $encrypt;
        } else {
            $this->receiver_mobile_no = null;
        }
        return $this;
    }

    public function getFullName(): ?string
    {
        $decrypt = $this->decrypt($this->full_name);
        return $decrypt;
    }

    public function setFullName(?string $full_name): static
    {
        $this->fullNamePlain = $full_name;
        if ($full_name !== null) {
            $encrypt = $this->encrypt($full_name);
            $this->full_name = $encrypt;
        } else {
            $this->full_name = null;
        }
        return $this;
    }


    public function getMobileNo(): ?string
    {
        $decrypt = $this->decrypt($this->mobile_no);
        return $decrypt;
    }

    public function setMobileNo(?string $mobile_no): static
    {
        $this->mobileNoPlain = $mobile_no;
        if ($mobile_no !== null) {
            $encrypt = $this->encrypt($mobile_no);
            $this->mobile_no = $encrypt;
        } else {
            $this->mobile_no = null;
        }
        return $this;
    }

    public function getEmail(): ?string
    {
        $decrypt = $this->decrypt($this->email);
        return $decrypt;
    }

    public function setEmail(?string $email): static
    {
        $this->emailPlain = $email;
        if ($email !== null) {
            $encrypt = $this->encrypt($email);
            $this->email = $encrypt;
        } else {
            $this->email = null;
        }
        return $this;
    }

    public function getNationalId(): ?string
    {
        $decrypt = $this->decrypt($this->national_id);
        return $decrypt;
    }

    public function setNationalId(?string $national_id): static
    {
        $this->nationalIdPlain = $national_id;
        if ($national_id !== null) {
            $encrypt = $this->encrypt($national_id);
            $this->national_id = $encrypt;
        } else {
            $this->national_id = null;
        }
        return $this;
    }

    public function getAddress1(): ?string
    {
        $decrypt = $this->decrypt($this->address_1);
        return $decrypt;
    }

    public function setAddress1(?string $address_1): static
    {
        $this->address1Plain = $address_1;
        if ($address_1 !== null) {
            $encrypt = $this->encrypt($address_1);
            $this->address_1 = $encrypt;
        } else {
            $this->address_1 = null;
        }
        return $this;
    }

    public function getAddress2(): ?string
    {
        $decrypt = $this->decrypt($this->address_2);
        return $decrypt;
    }

    public function setAddress2(?string $address_2): static
    {
        $encrypt = $this->encrypt($address_2);
        $this->address_2 = $encrypt;
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

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

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

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): static
    {
        $this->gender = $gender;

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

    public function getRejectReason(): ?string
    {
        return $this->reject_reason;
    }

    public function setRejectReason(?string $reject_reason): static
    {
        $this->reject_reason = $reject_reason;

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

    public function getField1(): ?string
    {
        return $this->field1;
    }

    public function setField1(?string $field1): static
    {
        $this->field1 = $field1;

        return $this;
    }

    public function getField2(): ?string
    {
        return $this->field2;
    }

    public function setField2(?string $field2): static
    {
        $this->field2 = $field2;

        return $this;
    }

    public function getField3(): ?string
    {
        return $this->field3;
    }

    public function setField3(?string $field3): static
    {
        $this->field3 = $field3;

        return $this;
    }

    public function getField4(): ?string
    {
        return $this->field4;
    }

    public function setField4(?string $field4): static
    {
        $this->field4 = $field4;

        return $this;
    }

    public function getField5(): ?string
    {
        return $this->field5;
    }

    public function setField5(?string $field5): static
    {
        $this->field5 = $field5;

        return $this;
    }

    public function getField6(): ?string
    {
        return $this->field6;
    }

    public function setField6(?string $field6): static
    {
        $this->field6 = $field6;

        return $this;
    }

    public function getField7(): ?string
    {
        return $this->field7;
    }

    public function setField7(string $field7): static
    {
        $this->field7 = $field7;

        return $this;
    }

    public function getField8(): ?string
    {
        return $this->field8;
    }

    public function setField8(?string $field8): static
    {
        $this->field8 = $field8;

        return $this;
    }

    public function getField9(): ?string
    {
        return $this->field9;
    }

    public function setField9(?string $field9): static
    {
        $this->field9 = $field9;

        return $this;
    }

    public function getField10(): ?string
    {
        return $this->field10;
    }

    public function setField10(?string $field10): static
    {
        $this->field10 = $field10;

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

    public function getProductRef(): ?string
    {
        return $this->product_ref;
    }

    public function setProductRef(?string $product_ref): static
    {
        $this->product_ref = $product_ref;

        return $this;
    }

    /**
     * Add a product ID to the product_ref field (comma-separated)
     */
    public function addProductRef(int $productId): static
    {
        if ($this->product_ref === null || $this->product_ref === '') {
            $this->product_ref = (string)$productId;
        } else {
            $existingIds = explode(',', $this->product_ref);
            if (!in_array((string)$productId, $existingIds)) {
                $this->product_ref .= ',' . $productId;
            }
        }
        return $this;
    }

    /**
     * Get array of product IDs from product_ref field
     */
    public function getProductRefArray(): array
    {
        if ($this->product_ref === null || $this->product_ref === '') {
            return [];
        }
        return array_map('intval', explode(',', $this->product_ref));
    }

    /**
     * Count how many products are assigned to this submission
     */
    public function getProductRefCount(): int
    {
        return count($this->getProductRefArray());
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

    public function getAttachmentNo(): ?string
    {
        return $this->attachment_no;
    }

    public function setAttachmentNo(?string $attachment_no): static
    {
        $this->attachment_no = $attachment_no;

        return $this;
    }

    public function getRCheckedDate(): ?\DateTimeInterface
    {
        return $this->r_checked_date;
    }

    public function setRCheckedDate(?\DateTimeInterface $r_checked_date): static
    {
        $this->r_checked_date = $r_checked_date;

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

    public function getSValidateDate(): ?\DateTimeInterface
    {
        return $this->s_validate_date;
    }

    public function setSValidateDate(?\DateTimeInterface $s_validate_date): static
    {
        $this->s_validate_date = $s_validate_date;

        return $this;
    }

    public function getRStatus(): ?string
    {
        return $this->r_status;
    }

    public function setRStatus(?string $r_status): static
    {
        $this->r_status = $r_status;

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setSub($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getSub() === $this) {
                $product->setSub(null);
            }
        }

        return $this;
    }

    public function getAttachment2(): ?string
    {
        return $this->attachment2;
    }

    public function setAttachment2(?string $attachment2): static
    {
        $this->attachment2 = $attachment2;

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

    public function getStatus2(): ?string
    {
        return $this->status2;
    }

    public function setStatus2(?string $status2): static
    {
        $this->status2 = $status2;

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

    public function getReason1(): ?string
    {
        return $this->reason1;
    }

    public function setReason1(?string $reason1): static
    {
        $this->reason1 = $reason1;

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

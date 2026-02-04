<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 300)]
    private ?string $full_name = null;

    #[ORM\Column(length: 25)]
    private ?string $mobile_no = null;

    #[ORM\Column(length: 256)]
    private ?string $email = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $national_id = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(nullable: true)]
    private ?int $age = null;

    #[ORM\Column(length: 25, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $cookie_pref = null;

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $token_uuid = null;

    #[ORM\Column]
    private ?bool $is_active = null;

    #[ORM\Column]
    private ?bool $is_guest = null;

    #[ORM\Column]
    private ?bool $is_visible = null;

    #[ORM\Column(nullable: true)]
    private ?int $submission_count = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created_date = null;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'user')]
    private Collection $products;

    /**
     * @var Collection<int, Submission>
     */
    #[ORM\OneToMany(targetEntity: Submission::class, mappedBy: 'user')]
    private Collection $submissions;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $last_login = null;

    #[ORM\Column]
    private ?bool $is_deleted = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_date = null;

    /**
     * @var Collection<int, Otp>
     */
    #[ORM\OneToMany(targetEntity: Otp::class, mappedBy: 'user')]
    private Collection $otps;

    /**
     * @var Collection<int, WinnerDetails>
     */
    #[ORM\OneToMany(targetEntity: WinnerDetails::class, mappedBy: 'user')]
    private Collection $winnerDetails;



    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->submissions = new ArrayCollection();
        $this->otps = new ArrayCollection();
        $this->winnerDetails = new ArrayCollection();
    }
 
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }



    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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

    public function getFullName(): ?string
    {
        $decrypt = $this->decrypt($this->full_name);
        return $decrypt;
    }

    public function setFullName(string $full_name): static
    {
        $encrypt = $this->encrypt($full_name);
        $this->full_name = $encrypt;
        return $this;
    }

    public function getMobileNo(): ?string
    {
        $decrypt = $this->decrypt($this->mobile_no);
        return $decrypt;
    }

    public function setMobileNo(string $mobile_no): static
    {
        $encrypt = $this->encrypt($mobile_no);
        $this->mobile_no = $encrypt;
        return $this;
    }

    public function getEmail(): ?string
    {
        $decrypt = $this->decrypt($this->email);
        return $decrypt;
    }

    public function setEmail(string $email): static
    {
        $encrypt = $this->encrypt($email);
        $this->email = $encrypt;
        return $this;
    }

    public function getNationalId(): ?string
    {
        $decrypt = $this->decrypt($this->national_id);
        return $decrypt;
    }

    public function setNationalId(?string $national_id): static
    {
        $encrypt = $this->encrypt($national_id);
        $this->national_id = $encrypt;
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

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): static
    {
        $this->age = $age;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCookiePref(): ?string
    {
        return $this->cookie_pref;
    }

    public function setCookiePref(?string $cookie_pref): static
    {
        $this->cookie_pref = $cookie_pref;

        return $this;
    }

    public function getSessionToken(): ?string
    {
        return $this->session_token;
    }

    public function setSessionToken(?string $session_token): static
    {
        $this->session_token = $session_token;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->is_active;
    }

    public function setActive(bool $is_active): static
    {
        $this->is_active = $is_active;

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

    public function getSubmissionCount(): ?int
    {
        return $this->submission_count;
    }

    public function setSubmissionCount(?int $submission_count): static
    {
        $this->submission_count = $submission_count;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function isGuest(): ?bool
    {
        return $this->is_guest;
    }

    public function setGuest(bool $is_guest): static
    {
        $this->is_guest = $is_guest;

        return $this;
    }

    public function isVisible(): ?bool
    {
        return $this->is_visible;
    }

    public function setVisible(bool $is_visible): static
    {
        $this->is_visible = $is_visible;

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
            $product->setUser($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getUser() === $this) {
                $product->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Submission>
     */
    public function getSubmissions(): Collection
    {
        return $this->submissions;
    }

    public function addSubmission(Submission $submission): static
    {
        if (!$this->submissions->contains($submission)) {
            $this->submissions->add($submission);
            $submission->setUser($this);
        }

        return $this;
    }

    public function removeSubmission(Submission $submission): static
    {
        if ($this->submissions->removeElement($submission)) {
            // set the owning side to null (unless already changed)
            if ($submission->getUser() === $this) {
                $submission->setUser(null);
            }
        }

        return $this;
    }

    public function getTokenUuid(): ?string
    {
        return $this->token_uuid;
    }

    public function setTokenUuid(?string $token_uuid): static
    {
        $this->token_uuid = $token_uuid;

        return $this;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->last_login;
    }

    public function setLastLogin(?\DateTimeInterface $last_login): static
    {
        $this->last_login = $last_login;

        return $this;
    }

    public function isDeleted(): ?bool
    {
        return $this->is_deleted;
    }

    public function setDeleted(bool $is_deleted): static
    {
        $this->is_deleted = $is_deleted;

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

    /**
     * @return Collection<int, Otp>
     */
    public function getOtps(): Collection
    {
        return $this->otps;
    }

    public function addOtp(Otp $otp): static
    {
        if (!$this->otps->contains($otp)) {
            $this->otps->add($otp);
            $otp->setUser($this);
        }

        return $this;
    }

    public function removeOtp(Otp $otp): static
    {
        if ($this->otps->removeElement($otp)) {
            // set the owning side to null (unless already changed)
            if ($otp->getUser() === $this) {
                $otp->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, WinnerDetails>
     */
    public function getWinnerDetails(): Collection
    {
        return $this->winnerDetails;
    }

    public function addWinnerDetail(WinnerDetails $winnerDetail): static
    {
        if (!$this->winnerDetails->contains($winnerDetail)) {
            $this->winnerDetails->add($winnerDetail);
            $winnerDetail->setUser($this);
        }

        return $this;
    }

    public function removeWinnerDetail(WinnerDetails $winnerDetail): static
    {
        if ($this->winnerDetails->removeElement($winnerDetail)) {
            // set the owning side to null (unless already changed)
            if ($winnerDetail->getUser() === $this) {
                $winnerDetail->setUser(null);
            }
        }

        return $this;
    }
}

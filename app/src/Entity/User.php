<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\CustomIdGenerator;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use OpenApi\Annotations as OA;
use OpenApi\Annotations\Items;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use Rollerworks\Component\PasswordStrength\Validator\Constraints\PasswordRequirements;
use Rollerworks\Component\PasswordStrength\Validator\Constraints\PasswordStrength;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class User.
 *
 * @author Vlad Dumitrache <vlad@vmdumitrache.dev>
 *
 * @ORM\Table(name="`user`")
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\HasLifecycleCallbacks
 */
class User implements UserInterface
{
    use Timestamps;

    public const STATUS_PENDING = 'status.pending';
    public const STATUS_VERIFIED = 'status.verified';
    public const STATUS_BLOCKED = 'status.blocked';

    public static array $userStatuses = [
        self::STATUS_PENDING,
        self::STATUS_VERIFIED,
        self::STATUS_BLOCKED,
    ];

    /**
     * @Id
     * @Column(type="uuid", unique=true)
     * @GeneratedValue(strategy="CUSTOM")
     * @CustomIdGenerator(class=UuidGenerator::class)
     *
     * @Groups({"non_sensitive_data"})
     *
     * @OA\Property(type="string", format="uuid")
     */
    private UuidInterface $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Groups({"non_sensitive_data"})
     */
    private string $email;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Groups({"non_sensitive_data"})
     */
    private string $firstName;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Groups({"non_sensitive_data"})
     */
    private string $lastName;

    /**
     * @ORM\Column(type="json")
     * @OA\Property(type="array", @Items(type="string"))
     * @Groups({"non_sensitive_data"})
     */
    private array $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private string $password;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max=4096)
     * @PasswordStrength(minLength="8", minStrength="4", message="The supplied password is too weak")
     * @PasswordRequirements(minLength="8", requireCaseDiff=true, requireNumbers=true, requireSpecialCharacter=true)
     */
    private ?string $plainPassword;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private bool $emailVerified = false;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private string $status = self::STATUS_PENDING;

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $id): User
    {
        $this->id = $id;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        $this->plainPassword = null;
    }

    /**
     * @return mixed
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param mixed $firstName
     */
    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param mixed $lastName
     */
    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): User
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function setEmailVerified(bool $emailVerified): User
    {
        $this->emailVerified = $emailVerified;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): User
    {
        $this->status = $status;

        return $this;
    }
}

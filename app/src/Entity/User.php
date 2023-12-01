<?php

namespace App\Entity;

use App\Repository\UserRepository;
use App\Security\AuthUserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class User
 *
 * An entity representing a user.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements PasswordAuthenticatedUserInterface, AuthUserInterface
{
    /**
     *  The unique identifier for the user.
     *
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Assert\Type(Integer::class, message: 'Incorrect type user id.')]
    private int $id;

    /**
     *  The name of the user.
     *
     * @var string
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Name cannot be empty.')]
    private string $name;

    /**
     *  The email address of the user.
     *
     * @var string
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Email cannot be empty.')]
    private string $email;

    /**
     *  The hashed password of the user. It is marked with #[Ignore] to avoid serialization.
     *
     * @var string
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Password cannot be empty.')]
    #[Ignore]
    private string $password;

    /**
     *  A collection of assets associated with the user.
     *
     * @var Collection
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Asset::class, orphanRemoval: true)]
    private Collection $assets;

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->assets = new ArrayCollection();
    }

    /**
     * Get the ID of the user.
     *
     * @return string The user's ID.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set the ID of the user.
     *
     * @param int $id The user's ID.
     *
     * @return $this
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the name of the user.
     *
     * @return string The user's name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name of the user.
     *
     * @param string $name The user's name.
     *
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the email address of the user.
     *
     * @return string The user's email address.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Set the email address of the user.
     *
     * @param string $email The user's email address.
     *
     * @return $this
     */
    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get the hashed password of the user.
     *
     * @return string The hashed password (not serialized).
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Set the hashed password of the user.
     *
     * @param string $password The hashed password (not serialized).
     *
     * @return $this
     */
    public function setPassword(string $password, UserPasswordHasher $hasher): static
    {
        $this->password = $hasher->hashPassword($this, $password);

        return $this;
    }

    /**
     * Get a collection of assets associated with the user.
     *
     * @return Collection<int, Asset> A collection of user's assets.
     */
    public function getAssets(): Collection
    {
        return $this->assets;
    }

    /**
     * Add an asset to the user's collection of assets.
     *
     * @param Asset $asset The asset to add.
     *
     * @return $this
     */
    public function addAsset(Asset $asset): static
    {
        if (!$this->assets->contains($asset)) {
            $this->assets->add($asset);
            $asset->setUser($this);
        }

        return $this;
    }

    /**
     * Get the roles associated with the user.
     *
     * @return array
     */
    public function getRoles(): array
    {
        return [
            'ROLE_USER',
        ];
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials(): void
    { }

    /**
     * Get the user's identifier.
     *
     * @return string
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }
}

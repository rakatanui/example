<?php

namespace App\Entity;

use App\Repository\AssetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Asset
 *
 * An entity representing an asset.
 */
#[ORM\Entity(repositoryClass: AssetRepository::class)]
class Asset
{
    const AVAILABLE_CURRENCY = ['BTC', 'ETH', 'IOTA'];
    /**
     *  The unique identifier for the asset.
     *
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Assert\Type(Integer::class, message: 'Incorrect type user id.')]
    private int $id;

    /**
     *  The user associated with the asset, or null if not associated.
     *
     * @var User|null
     */
    #[ORM\ManyToOne(fetch: 'EXTRA_LAZY', inversedBy: 'assets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user;

    /**
     *  The label or name of the asset.
     *
     * @var string
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Label cannot be empty.')]
    private string $label;

    /**
     *  The currency of the asset, chosen from a set of valid options (BTC, ETH, IOTA).
     *
     * @var string
     */
    #[ORM\Column(length: 255)]
    #[Assert\Choice(choices: self::AVAILABLE_CURRENCY, message: 'Invalid currency.')]
    #[Assert\NotBlank(message: 'Currency cannot be empty.')]
    private string $currency;

    /**
     *  The value of the asset, represented as a decimal number with a maximum precision of 10 digits and a scale of 2.
     *
     * @var string
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\PositiveOrZero(message: 'Value cannot be negative.')]
    #[Assert\NotBlank(message: 'Value cannot be empty.')]
    private string $value;

    /**
     * Get the ID of the asset.
     *
     * @return int The asset's ID.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the ID of the asset.
     *
     * @param int $id The asset's ID.
     *
     * @return $this
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the user associated with the asset.
     *
     * @return User|null The user associated with the asset.
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Set the user associated with the asset.
     *
     * @param User|null $user The user associated with the asset.
     *
     * @return $this
     */
    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the label of the asset.
     *
     * @return string|null The asset's label.
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Set the label of the asset.
     *
     * @param string $label The asset's label.
     *
     * @return $this
     */
    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the currency of the asset.
     *
     * @return string The asset's currency.
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Set the currency of the asset.
     *
     * @param string $currency The asset's currency.
     *
     * @return $this
     */
    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get the value of the asset.
     *
     * @return string The asset's value.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Set the value of the asset.
     *
     * @param string $value The asset's value.
     *
     * @return $this
     */
    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }
}

<?php

namespace App\Entity;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshTokenRepository;
use Gesdinet\JWTRefreshTokenBundle\Model\AbstractRefreshToken as BaseAbstractRefreshToken;

/**
 * Class RefreshToken
 *
 * Represents a refresh token entity for managing JWT token refreshment.
 */
#[ORM\Entity(repositoryClass: RefreshTokenRepository::class)]
#[ORM\Table('refresh_tokens')]
class RefreshToken extends BaseAbstractRefreshToken
{
    /**
     * Unique identifier for the refresh token.
     *
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /**
     * The actual refresh token string.
     *
     * @var string
     */
    #[ORM\Column(name: 'refresh_token', type: Types::STRING, nullable: false)]
    protected $refreshToken;

    /**
     * The username associated with the refresh token.
     *
     * @var string
     */
    #[ORM\Column(name: 'username', type: Types::STRING, nullable: false)]
    protected $username;

    /**
     * The date and time when the refresh token is valid.
     *
     * @var DateTime
     */
    #[ORM\Column(name: 'valid', type: Types::DATETIME_MUTABLE, nullable: false)]
    protected $valid;
}
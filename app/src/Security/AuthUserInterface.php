<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Interface AuthUserInterface
 *
 * Represents a user for authentication purposes.
 */
interface AuthUserInterface extends UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * Get the unique identifier for the user.
     *
     * @return string The user's unique identifier.
     */
    public function getId(): string;

    /**
     * Get the email address of the user.
     *
     * @return string The user's email address.
     */
    public function getEmail(): string;
}

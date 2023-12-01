<?php

namespace App\Security;

use Symfony\Bundle\SecurityBundle\Security;
use Webmozart\Assert\Assert;

/**
 * Class UserFetcher
 *
 * A class that implements the UserFetcherInterface for fetching an authenticated user.
 */
class UserFetcher implements UserFetcherInterface
{
    /**
     * Constructor for the UserFetcher class.
     *
     * @param Security $security The Symfony Security component for managing security features.
     */
    public function __construct(private readonly Security $security)
    {
    }

    /**
     * Get the authenticated user.
     *
     * @return AuthUserInterface The authenticated user.
     */
    public function getAuthUser(): AuthUserInterface
    {
        /** @var AuthUserInterface $user */
        $user = $this->security->getUser();

        Assert::notNull($user, 'Current user not found check security access list');
        Assert::isInstanceOf($user, AuthUserInterface::class, sprintf('Invalid user type %s', \get_class($user)));

        return $user;
    }
}

<?php

namespace App\Security;

/**
 * Interface UserFetcherInterface
 *
 * An interface for fetching an authenticated user.
 */
interface UserFetcherInterface
{
    /**
     * Get the authenticated user.
     *
     * @return AuthUserInterface The authenticated user.
     */
    public function getAuthUser(): AuthUserInterface;
}

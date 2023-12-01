<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Class AppFixtures
 *
 * Fixture class to load initial data into the database.
 */
class AppFixtures extends Fixture
{
    /**
     *  The password hasher service to securely hash user passwords.
     *
     * @param UserPasswordHasherInterface $hasher Interface for hashing password
     */
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    )
    {
    }

    /**
     * Load initial data into the database.
     *
     * @param ObjectManager $manager The Doctrine object manager.
     *
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 10; $i++) {
            $username = 'user_' . $i;

            $user = new User();
            $user->setName($username);
            $user->setEmail($username . '@test.com');

            $password = 'password_' . $i;
            $user->setPassword($password, $this->hasher);

            $manager->persist($user);
        }

        $manager->flush();
    }
}

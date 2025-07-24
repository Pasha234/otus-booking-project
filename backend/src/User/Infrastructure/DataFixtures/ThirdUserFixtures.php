<?php

namespace App\User\Infrastructure\DataFixtures;

use App\User\Domain\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ThirdUserFixtures extends Fixture
{
    public const THIRD_USER_REFERENCE = 'third_user';

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('searchable.user@test.com');
        $user->setFullName('Searchable User');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));

        $manager->persist($user);
        $manager->flush();

        $this->addReference(self::THIRD_USER_REFERENCE, $user);
    }
}
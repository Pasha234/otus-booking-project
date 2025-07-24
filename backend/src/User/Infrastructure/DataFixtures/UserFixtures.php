<?php

namespace App\User\Infrastructure\DataFixtures;

use App\Shared\Infrastructure\Tools\WithFaker;
use App\User\Domain\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    use WithFaker;

    public function __construct(
        private UserPasswordHasherInterface $hasher
    )
    {
        
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setFullName($this->faker()->name());
        $user->setPassword($this->hasher->hashPassword($user, 'test'));
        $user->setEmail($this->faker()->email());
        $manager->persist($user);
        $manager->flush();

        $this->addReference('user', $user);
    }
}
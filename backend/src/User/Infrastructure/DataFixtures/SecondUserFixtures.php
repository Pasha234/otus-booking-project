<?php

namespace App\User\Infrastructure\DataFixtures;

use App\User\Domain\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecondUserFixtures extends Fixture
{
    public const SECOND_USER_REFERENCE = 'second_user';

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        $user = new User();
        $user->setEmail('second.user@test.com');
        $user->setFullName($faker->name());
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));

        $manager->persist($user);
        $manager->flush();

        $this->addReference(self::SECOND_USER_REFERENCE, $user);
    }
}
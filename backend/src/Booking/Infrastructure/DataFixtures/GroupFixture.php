<?php

namespace App\Booking\Infrastructure\DataFixtures;

use App\Booking\Domain\Entity\Group;
use App\Shared\Infrastructure\Tools\WithFaker;
use App\User\Domain\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class GroupFixture extends Fixture
{
    use WithFaker;

    public function load(ObjectManager $manager): void
    {
        $group = new Group();
        $group->setName($this->faker()->word());
        $group->setDescription($this->faker()->sentence());
        $group->setOwner($this->getReference('user', User::class));

        $manager->persist($group);
        $manager->flush();

        $this->addReference('group', $group);
    }
}
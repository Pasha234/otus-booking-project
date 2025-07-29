<?php

namespace App\Booking\Infrastructure\DataFixtures;

use App\Booking\Domain\Entity\Group;
use App\Booking\Domain\Entity\Resource;
use App\Shared\Infrastructure\Tools\WithFaker;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class ResourceFixture extends Fixture
{
    use WithFaker;

    public function load(ObjectManager $manager): void
    {
        $resource = new Resource();
        $resource->setName($this->faker()->word());
        $resource->setQuantity($this->faker()->numberBetween(1, 20));
        $resource->setIsActive(true);
        $resource->setGroup($this->getReference('group', Group::class));

        $manager->persist($resource);
        $manager->flush();

        $this->addReference('resource', $resource);
    }
}
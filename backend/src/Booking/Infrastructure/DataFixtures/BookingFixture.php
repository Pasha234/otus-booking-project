<?php

namespace App\Booking\Infrastructure\DataFixtures;

use App\Booking\Domain\Entity\BookedResource;
use App\Booking\Domain\Entity\Booking;
use App\Booking\Domain\Entity\Resource;
use App\Shared\Infrastructure\Tools\WithFaker;
use App\User\Domain\Entity\User;
use DateTimeImmutable;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class BookingFixture extends Fixture
{
    use WithFaker;

    public function load(ObjectManager $manager): void
    {
        $booking = new Booking();
        $booking->setTitle($this->faker()->word());
        $booking->setDescription($this->faker()->sentence());
        $booking->setStartAt(DateTimeImmutable::createFromInterface($this->faker()->dateTimeBetween()));
        $booking->setEndAt(DateTimeImmutable::createFromInterface($this->faker()->dateTimeBetween('now', '+30 years')));
        $booking->setAuthor($this->getReference('user', User::class));
        $booking->addUser($this->getReference('user', User::class));

        $bookedResource = new BookedResource();
        $bookedResource->setResource($this->getReference('resource', Resource::class));
        $bookedResource->setQuantity($this->faker()->numberBetween(1, $this->getReference('resource', Resource::class)->getQuantity()));

        $booking->addBookedResource($bookedResource);

        $manager->persist($booking);
        $manager->flush();

        $this->addReference('booking', $booking);
    }
}
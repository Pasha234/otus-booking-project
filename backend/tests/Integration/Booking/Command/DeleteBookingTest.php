<?php

namespace App\Tests\Integration\Booking\Command;

use App\Booking\Application\Command\DeleteBooking\DeleteBookingCommand;
use App\Booking\Application\Command\DeleteBooking\DeleteBookingCommandHandler;
use App\Booking\Domain\Entity\Booking;
use App\Booking\Domain\Entity\Group;
use App\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Shared\Application\Exception\NotFoundInRepositoryException;
use App\User\Domain\Entity\User;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DeleteBookingTest extends WebTestCase
{
    private Generator $faker;
    private EntityManagerInterface $entityManager;
    private DeleteBookingCommandHandler $handler;
    private BookingRepositoryInterface $bookingRepository;

    private Booking $booking;

    public function setUp(): void
    {
        parent::setUp();

        $container = self::getContainer();

        $this->handler = $container->get(DeleteBookingCommandHandler::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->bookingRepository = $container->get(BookingRepositoryInterface::class);
        $this->faker = Factory::create();

        // Clean the database
        $purger = new ORMPurger($this->entityManager);
        $purger->purge();

        // Create test data
        $this->createTestData();
    }

    private function createTestData(): void
    {
        // 1. Create a user
        $author = new User();
        $author->setEmail($this->faker->email())->setFullName($this->faker->name())->setPassword('password');
        $this->entityManager->persist($author);

        // 2. Create a group
        $group = new Group();
        $group->setName($this->faker->company())->setDescription($this->faker->sentence())->setOwner($author);
        $this->entityManager->persist($group);

        // 3. Create a booking
        $this->booking = new Booking();
        $this->booking
            ->setGroup($group)
            ->setAuthor($author)
            ->setTitle('Test Booking for Deletion')
            ->setStartAt(new \DateTimeImmutable('+1 day'))
            ->setEndAt(new \DateTimeImmutable('+1 day +1 hour'));
        $this->entityManager->persist($this->booking);

        $this->entityManager->flush();
        $this->entityManager->clear(); // Detach all entities
    }

    public function test_should_delete_booking(): void
    {
        // Arrange
        $bookingId = $this->booking->getId();

        // Pre-assertion: ensure booking exists
        $bookingBeforeDelete = $this->bookingRepository->getById($bookingId);
        $this->assertNotNull($bookingBeforeDelete);

        $command = new DeleteBookingCommand($bookingId);

        // Act
        ($this->handler)($command);

        // Assert
        $this->entityManager->clear();
        $bookingAfterDelete = $this->bookingRepository->getById($bookingId);
        $this->assertNull($bookingAfterDelete);
    }

    public function test_should_throw_exception_when_booking_not_found(): void
    {
        // Arrange
        $nonExistentBookingId = '00000000-0000-0000-0000-000000000000';
        $command = new DeleteBookingCommand($nonExistentBookingId);

        // Assert
        $this->expectException(NotFoundInRepositoryException::class);

        // Act
        ($this->handler)($command);
    }
}
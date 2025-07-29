<?php

namespace App\Tests\Integration\Booking\Query;

use App\Booking\Application\DTO\GetBookingList\BookingDTO;
use App\Booking\Application\Query\FindBookingById\FindBookingByIdQuery;
use App\Booking\Application\Query\FindBookingById\FindBookingByIdQueryHandler;
use App\Booking\Domain\Entity\BookedResource;
use App\Booking\Domain\Entity\Booking;
use App\Booking\Domain\Entity\Group;
use App\Booking\Domain\Entity\GroupParticipant;
use App\Booking\Domain\Entity\Resource;
use App\User\Domain\Entity\User;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FindBookingByIdTest extends WebTestCase
{
    private Generator $faker;
    private EntityManagerInterface $entityManager;
    private FindBookingByIdQueryHandler $handler;

    // Test data entities
    private User $author;
    private User $participant;
    private Group $group;
    private Resource $resource;
    private Booking $booking;

    public function setUp(): void
    {
        parent::setUp();

        $container = self::getContainer();

        $this->handler = $container->get(FindBookingByIdQueryHandler::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->faker = Factory::create();

        $purger = new ORMPurger($this->entityManager);
        $purger->purge();

        $this->createTestData();
    }

    private function createTestData(): void
    {
        // Users
        $this->author = new User();
        $this->author->setEmail($this->faker->email())->setFullName('Author User')->setPassword('p');
        $this->participant = new User();
        $this->participant->setEmail($this->faker->email())->setFullName('Participant User')->setPassword('p');
        $this->entityManager->persist($this->author);
        $this->entityManager->persist($this->participant);

        // Group
        $this->group = new Group();
        $this->group->setName('Test Group')->setOwner($this->author);
        $this->entityManager->persist($this->group);

        // Group Participants
        $gpAuthor = new GroupParticipant();
        $gpAuthor->setUser($this->author)->setGroup($this->group);
        $this->entityManager->persist($gpAuthor);

        $gpParticipant = new GroupParticipant();
        $gpParticipant->setUser($this->participant)->setGroup($this->group);
        $this->entityManager->persist($gpParticipant);

        // Resource
        $this->resource = new Resource();
        $this->resource->setName('Test Resource')->setQuantity(10)->setGroup($this->group)->setIsActive(true);
        $this->entityManager->persist($this->resource);

        // Booking
        $this->booking = new Booking();
        $this->booking
            ->setGroup($this->group)
            ->setAuthor($this->author)
            ->setTitle('Test Booking')
            ->setDescription('Test Description')
            ->setStartAt(new \DateTimeImmutable('tomorrow 10:00'))
            ->setEndAt(new \DateTimeImmutable('tomorrow 11:00'));

        $this->booking->addUser($this->participant);

        $bookedResource = new BookedResource();
        $bookedResource->setResource($this->resource)->setQuantity(2);
        $this->booking->addBookedResource($bookedResource);

        $this->entityManager->persist($this->booking);
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function test_should_return_booking_dto_when_booking_exists(): void
    {
        // Arrange
        $query = new FindBookingByIdQuery($this->booking->getId());

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertInstanceOf(BookingDTO::class, $result);
        $this->assertEquals($this->booking->getId(), $result->id);
        $this->assertEquals('Test Booking', $result->title);
        $this->assertEquals('Test Description', $result->description);
        $this->assertEquals($this->author->getId(), $result->author->id);
        $this->assertEquals('Author User', $result->author->full_name);

        $this->assertCount(1, $result->users);
        $this->assertEquals($this->participant->getId(), $result->users[0]->id);

        $this->assertCount(1, $result->booked_resources);
        $this->assertEquals($this->resource->getId(), $result->booked_resources[0]->resource->id);
        $this->assertEquals(2, $result->booked_resources[0]->quantity);
    }

    public function test_should_return_null_when_booking_does_not_exist(): void
    {
        // Arrange
        $nonExistentId = '00000000-0000-0000-0000-000000000000';
        $query = new FindBookingByIdQuery($nonExistentId);

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertNull($result);
    }
}
<?php

namespace App\Tests\Integration\Booking\Command;

use App\Booking\Application\Command\CreateBooking\CreateBookingCommand;
use App\Booking\Application\Command\CreateBooking\CreateBookingCommandHandler;
use App\Booking\Domain\Entity\Group;
use App\Booking\Domain\Entity\GroupParticipant;
use App\Booking\Domain\Entity\Resource;
use App\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Shared\Application\Exception\NotFoundInRepositoryException;
use App\User\Domain\Entity\User;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CreateBookingTest extends WebTestCase
{
    private Generator $faker;
    private EntityManagerInterface $entityManager;
    private CreateBookingCommandHandler $handler;
    private BookingRepositoryInterface $bookingRepository;

    // We'll store created entities here to access them in tests
    private User $groupOwner;
    private User $groupMember;
    private Group $group;
    private Resource $resource;

    public function setUp(): void
    {
        parent::setUp();

        $container = self::getContainer();

        $this->handler = $container->get(CreateBookingCommandHandler::class);
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
        // 1. Create users
        $this->groupOwner = new User();
        $this->groupOwner->setEmail($this->faker->email())->setFullName($this->faker->name())->setPassword('password');

        $this->groupMember = new User();
        $this->groupMember->setEmail($this->faker->email())->setFullName($this->faker->name())->setPassword('password');

        $this->entityManager->persist($this->groupOwner);
        $this->entityManager->persist($this->groupMember);

        // 2. Create a group
        $this->group = new Group();
        $this->group->setName($this->faker->company())->setDescription($this->faker->sentence())->setOwner($this->groupOwner);

        $this->entityManager->persist($this->group);

        // 3. Add users as participants to the group
        $ownerParticipant = new GroupParticipant();
        $ownerParticipant->setUser($this->groupOwner)->setGroup($this->group);
        $this->entityManager->persist($ownerParticipant);

        $memberParticipant = new GroupParticipant();
        $memberParticipant->setUser($this->groupMember)->setGroup($this->group);
        $this->entityManager->persist($memberParticipant);

        // 4. Create a resource for the group
        $this->resource = new Resource();
        $this->resource->setName('Projector')->setQuantity(5)->setGroup($this->group)->setIsActive(true);

        $this->entityManager->persist($this->resource);

        $this->entityManager->flush();
        $this->entityManager->clear(); // Detach all entities
    }

    public function test_should_create_booking_with_participants_and_resources(): void
    {
        // Arrange
        $title = 'Team Meeting';
        $description = 'Discuss project progress';
        $startAt = new \DateTimeImmutable('tomorrow 10:00');
        $endAt = new \DateTimeImmutable('tomorrow 11:00');
        $requestedQuantity = 2;

        $command = new CreateBookingCommand(
            $this->group->getId(),
            $this->groupOwner->getId(),
            $title,
            $description,
            $startAt,
            $endAt,
            [['id' => $this->resource->getId(), 'quantity' => $requestedQuantity]],
            [$this->groupMember->getId()],
        );

        // Act
        ($this->handler)($command);

        // Assert
        $this->entityManager->clear();

        $bookings = $this->bookingRepository->findBy(['title' => $title]);
        $this->assertCount(1, $bookings);
        $booking = $bookings[0];

        $this->assertEquals($title, $booking->getTitle());
        $this->assertEquals($description, $booking->getDescription());
        $this->assertEquals($this->groupOwner->getId(), $booking->getAuthor()->getId());
        $this->assertEquals($this->group->getId(), $booking->getGroup()->getId());
        $this->assertEquals($startAt, $booking->getStartAt());
        $this->assertEquals($endAt, $booking->getEndAt());

        // Check participants
        $participants = $booking->getUsers();
        $this->assertCount(1, $participants);
        $this->assertEquals($this->groupMember->getId(), $participants->first()->getId());

        // Check booked resources
        $bookedResources = $booking->getBookedResources();
        $this->assertCount(1, $bookedResources);
        $bookedResource = $bookedResources->first();
        $this->assertEquals($this->resource->getId(), $bookedResource->getResource()->getId());
        $this->assertEquals($requestedQuantity, $bookedResource->getQuantity());
    }

    public function test_should_throw_exception_when_author_is_not_group_member(): void
    {
        // Arrange
        $nonMember = new User();
        $nonMember->setEmail($this->faker->email())->setFullName($this->faker->name())->setPassword('p');
        $this->entityManager->persist($nonMember);
        $this->entityManager->flush();

        $command = new CreateBookingCommand(
            $this->group->getId(),
            $nonMember->getId(),
            'Invalid Meeting',
            'This should fail',
            new \DateTimeImmutable('tomorrow 10:00'),
            new \DateTimeImmutable('tomorrow 11:00'),
            [],
            []
        );

        // Assert
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('User is not a member of this group.');

        // Act
        ($this->handler)($command);
    }

    public function test_should_throw_exception_when_requesting_more_resources_than_available(): void
    {
        // Arrange
        $unavailableQuantity = $this->resource->getQuantity() + 1;
        $command = new CreateBookingCommand(
            $this->group->getId(),
            $this->groupOwner->getId(),
            'Resource Overbook',
            'Trying to book too many projectors',
            new \DateTimeImmutable('tomorrow 14:00'),
            new \DateTimeImmutable('tomorrow 15:00'),
            [['id' => $this->resource->getId(), 'quantity' => $unavailableQuantity]],
            [],
        );

        // Assert
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Not enough quantity for resource: \"{$this->resource->getName()}\". Requested: {$unavailableQuantity}, Available: {$this->resource->getQuantity()}");

        // Act
        ($this->handler)($command);
    }

    public function test_should_throw_exception_for_non_existent_participant(): void
    {
        // Arrange
        $nonExistentUserId = '00000000-0000-0000-0000-000000000000';
        $command = new CreateBookingCommand(
            $this->group->getId(),
            $this->groupOwner->getId(),
            'Ghost Participant',
            'Booking with a ghost',
            new \DateTimeImmutable('tomorrow 16:00'),
            new \DateTimeImmutable('tomorrow 17:00'),
            [],
            [$nonExistentUserId],
        );

        // Assert
        $this->expectException(NotFoundInRepositoryException::class);
        $this->expectExceptionMessage("User with id {$nonExistentUserId} not found in group");

        // Act
        ($this->handler)($command);
    }

    public function test_should_throw_exception_for_non_existent_resource(): void
    {
        // Arrange
        $nonExistentResourceId = '00000000-0000-0000-0000-000000000000';
        $command = new CreateBookingCommand(
            $this->group->getId(),
            $this->groupOwner->getId(),
            'Ghost Participant',
            'Booking with a ghost',
            new \DateTimeImmutable('tomorrow 16:00'),
            new \DateTimeImmutable('tomorrow 17:00'),
            [[
                'id' => $nonExistentResourceId,
                'quantity' => 1,
            ]
            ],
            [],
        );

        // Assert
        $this->expectException(NotFoundInRepositoryException::class);
        $this->expectExceptionMessage("Resource with id {$nonExistentResourceId} does not exist");

        // Act
        ($this->handler)($command);
    }
}
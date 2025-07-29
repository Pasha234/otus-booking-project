<?php

namespace App\Tests\Integration\Booking\Command;

use App\Booking\Application\Command\UpdateBooking\UpdateBookingCommand;
use App\Booking\Application\Command\UpdateBooking\UpdateBookingCommandHandler;
use App\Booking\Domain\Entity\Booking;
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

class UpdateBookingTest extends WebTestCase
{
    private Generator $faker;
    private EntityManagerInterface $entityManager;
    private UpdateBookingCommandHandler $handler;
    private BookingRepositoryInterface $bookingRepository;

    // Test data entities
    private User $author;
    private User $participant1;
    private User $participant2;
    private Group $group;
    private Resource $resource1;
    private Resource $resource2;
    private Booking $booking;

    public function setUp(): void
    {
        parent::setUp();

        $container = self::getContainer();

        $this->handler = $container->get(UpdateBookingCommandHandler::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->bookingRepository = $container->get(BookingRepositoryInterface::class);
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
        $this->participant1 = new User();
        $this->participant1->setEmail($this->faker->email())->setFullName('Participant One')->setPassword('p');
        $this->participant2 = new User();
        $this->participant2->setEmail($this->faker->email())->setFullName('Participant Two')->setPassword('p');
        $this->entityManager->persist($this->author);
        $this->entityManager->persist($this->participant1);
        $this->entityManager->persist($this->participant2);

        // Group
        $this->group = new Group();
        $this->group->setName('Test Group')->setOwner($this->author);
        // $this->entityManager->persist($this->group);

        // Group Participants
        $gpAuthor = new GroupParticipant();
        $gpAuthor->setUser($this->author);
        $this->group->addGroupParticipant($gpAuthor);
        $gpP1 = new GroupParticipant();
        $gpP1->setUser($this->participant1);
        $this->group->addGroupParticipant($gpP1);
        $gpP2 = new GroupParticipant();
        $gpP2->setUser($this->participant2);
        $this->group->addGroupParticipant($gpP2);
        $this->entityManager->persist($this->group);
        // $this->entityManager->persist($gpAuthor);
        // $this->entityManager->persist($gpP1);
        // $this->entityManager->persist($gpP2);

        // Resources
        $this->resource1 = new Resource();
        $this->resource1->setName('Projector')->setQuantity(5)->setGroup($this->group)->setIsActive(true);
        $this->resource2 = new Resource();
        $this->resource2->setName('Whiteboard')->setQuantity(1)->setGroup($this->group)->setIsActive(true);
        $this->entityManager->persist($this->resource1);
        $this->entityManager->persist($this->resource2);

        // Initial Booking
        $this->booking = new Booking();
        $this->booking
            ->setGroup($this->group)
            ->setAuthor($this->author)
            ->setTitle('Initial Booking')
            ->setDescription('Initial Description')
            ->setStartAt(new \DateTimeImmutable('tomorrow 10:00'))
            ->setEndAt(new \DateTimeImmutable('tomorrow 11:00'));

        $this->booking->addUser($this->participant1);

        $bookedResource = new \App\Booking\Domain\Entity\BookedResource();
        $bookedResource->setResource($this->resource1)->setQuantity(1);
        $this->booking->addBookedResource($bookedResource);

        $this->entityManager->persist($this->booking);
        $this->entityManager->flush();
    }

    public function test_should_update_all_booking_fields(): void
    {
        // Arrange
        $newTitle = 'Updated Booking Title';
        $newDescription = 'Updated Description';
        $newStartAt = new \DateTimeImmutable('next week 14:00');
        $newEndAt = new \DateTimeImmutable('next week 16:00');
        $newParticipantIds = [$this->participant2->getId()];
        $newResources = [
            ['id' => $this->resource1->getId(), 'quantity' => 2],
            ['id' => $this->resource2->getId(), 'quantity' => 1],
        ];

        $command = new UpdateBookingCommand(
            $this->booking->getId(), 
            $newTitle, 
            $newDescription, 
            $newStartAt, 
            $newEndAt, 
            $newResources,
            $newParticipantIds, 
        );

        // Act
        ($this->handler)($command);

        // Assert
        $this->entityManager->clear();
        $updatedBooking = $this->bookingRepository->getById($this->booking->getId());

        $this->assertNotNull($updatedBooking);
        $this->assertEquals($newTitle, $updatedBooking->getTitle());
        $this->assertEquals($newDescription, $updatedBooking->getDescription());
        $this->assertEquals($newStartAt, $updatedBooking->getStartAt());
        $this->assertEquals($newEndAt, $updatedBooking->getEndAt());

        $participants = $updatedBooking->getUsers();
        $this->assertCount(1, $participants);
        $this->assertEquals($this->participant2->getId(), $participants->first()->getId());

        $bookedResources = $updatedBooking->getBookedResources();
        $this->assertCount(2, $bookedResources);
        $bookedResourcesById = [];
        foreach ($bookedResources as $br) {
            $bookedResourcesById[$br->getResource()->getId()] = $br->getQuantity();
        }
        $this->assertEquals(2, $bookedResourcesById[$this->resource1->getId()]);
        $this->assertEquals(1, $bookedResourcesById[$this->resource2->getId()]);
    }

    public function test_should_throw_exception_if_booking_not_found(): void
    {
        $command = new UpdateBookingCommand('00000000-0000-0000-0000-000000000000', 'title');
        $this->expectException(NotFoundInRepositoryException::class);
        $this->expectExceptionMessage('Booking not found');
        ($this->handler)($command);
    }

    public function test_should_throw_exception_if_start_date_is_after_end_date(): void
    {
        $command = new UpdateBookingCommand(
            $this->booking->getId(), null, null, new \DateTimeImmutable('tomorrow 11:00'), new \DateTimeImmutable('tomorrow 10:00')
        );
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Start date must be before end date.');
        ($this->handler)($command);
    }

    public function test_should_throw_exception_if_participant_not_found(): void
    {
        $nonExistentUserId = '00000000-0000-0000-0000-000000000000';
        $command = new UpdateBookingCommand(
            $this->booking->getId(), 
            null, 
            null, 
            null, 
            null, 
            null,
            [$nonExistentUserId],

        );
        $this->expectException(NotFoundInRepositoryException::class);
        $this->expectExceptionMessage("User with id {$nonExistentUserId} not found");
        ($this->handler)($command);
    }

    public function test_should_throw_exception_if_participant_not_in_group(): void
    {
        $nonGroupMember = new User();
        $nonGroupMember->setEmail($this->faker->email())->setFullName('Outsider')->setPassword('p');
        $this->entityManager->persist($nonGroupMember);
        $this->entityManager->flush();

        $command = new UpdateBookingCommand(
            $this->booking->getId(),
            null,
            null,
            null,
            null,
            null,
            [$nonGroupMember->getId()]
        );
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("User {$nonGroupMember->getFullName()} is not a member of the group.");
        ($this->handler)($command);
    }

    public function test_should_throw_exception_if_not_enough_resource_quantity(): void
    {
        $tooMany = $this->resource1->getQuantity() + 1;
        $command = new UpdateBookingCommand(
            $this->booking->getId(), 
            null, 
            null, 
            null, 
            null, 
            [['id' => $this->resource1->getId(), 'quantity' => $tooMany]],
            null, 
        );
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Not enough quantity for resource: \"{$this->resource1->getName()}\". Requested: {$tooMany}, Available: {$this->resource1->getQuantity()}");
        ($this->handler)($command);
    }

    public function test_should_correctly_calculate_available_resources_when_other_bookings_exist(): void
    {
        $otherBooking = new Booking();
        $otherBooking
            ->setGroup($this->group)->setAuthor($this->author)->setTitle('Other Booking')
            ->setStartAt(new \DateTimeImmutable('tomorrow 12:00'))->setEndAt(new \DateTimeImmutable('tomorrow 13:00'));
        $bookedResource = new \App\Booking\Domain\Entity\BookedResource();
        $bookedResource->setResource($this->resource1)->setQuantity(4); // resource1 has 5 total
        $otherBooking->addBookedResource($bookedResource);
        $this->entityManager->persist($otherBooking);
        $this->entityManager->flush();

        // Try to update original booking to use 2 of resource1 during an overlapping time. Available is 1 (5 total - 4 booked).
        $command = new UpdateBookingCommand(
            $this->booking->getId(), 
            null, 
            null, 
            new \DateTimeImmutable('tomorrow 12:30'), 
            new \DateTimeImmutable('tomorrow 13:30'), 
            [['id' => $this->resource1->getId(), 'quantity' => 2]],
            null, 
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Not enough quantity for resource: \"{$this->resource1->getName()}\". Requested: 2, Available: 1");
        ($this->handler)($command);
    }

    public function test_should_throw_exception_if_resource_not_in_group(): void
    {
        $otherGroup = new Group();
        $otherGroup->setName('Other Group')->setOwner($this->author);
        $this->entityManager->persist($otherGroup);

        $otherResource = new Resource();
        $otherResource->setName('Alien Resource')->setQuantity(10)->setGroup($otherGroup)->setIsActive(true);
        $this->entityManager->persist($otherResource);
        $this->entityManager->flush();

        $command = new UpdateBookingCommand(
            $this->booking->getId(), 
            null, 
            null, 
            null, 
            null, 
            [['id' => $otherResource->getId(), 'quantity' => 1]],
            null,

        );

        $this->expectException(NotFoundInRepositoryException::class);
        $this->expectExceptionMessage("Resource {$otherResource->getId()} does not belong to this group.");
        ($this->handler)($command);
    }
}
<?php

namespace App\Tests\Integration\Booking\Query;

use App\Booking\Application\DTO\Basic\ResourceWithAvailableDTO;
use App\Booking\Application\Query\GetResourcesForGroupWithAvailableQuantity\GetResourcesForGroupWithAvailableQuantityQuery;
use App\Booking\Application\Query\GetResourcesForGroupWithAvailableQuantity\GetResourcesForGroupWithAvailableQuantityQueryHandler;
use App\Booking\Domain\Entity\BookedResource;
use App\Booking\Domain\Entity\Booking;
use App\Booking\Domain\Entity\Group;
use App\Booking\Domain\Entity\Resource;
use App\User\Domain\Entity\User;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GetResourcesForGroupWithAvailableQuantityTest extends WebTestCase
{
    private Generator $faker;
    private EntityManagerInterface $entityManager;
    private GetResourcesForGroupWithAvailableQuantityQueryHandler $handler;

    // Test data entities
    private Group $group;
    private Resource $resource1; // Projectors, Qty: 5
    private Resource $resource2; // Whiteboards, Qty: 2
    private Booking $conflictingBooking;
    private Booking $bookingToUpdate;

    public function setUp(): void
    {
        parent::setUp();
        $container = self::getContainer();

        $this->handler = $container->get(GetResourcesForGroupWithAvailableQuantityQueryHandler::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->faker = Factory::create();

        $purger = new ORMPurger($this->entityManager);
        $purger->purge();

        $this->createTestData();
    }

    private function createTestData(): void
    {
        $user = new User();
        $user->setEmail($this->faker->email())->setFullName($this->faker->name())->setPassword('p');
        $this->entityManager->persist($user);

        $this->group = new Group();
        $this->group->setName('Test Group')->setOwner($user);
        $this->entityManager->persist($this->group);

        $this->resource1 = new Resource();
        $this->resource1->setName('Projector')->setQuantity(5)->setGroup($this->group)->setIsActive(true);
        $this->entityManager->persist($this->resource1);

        $this->resource2 = new Resource();
        $this->resource2->setName('Whiteboard')->setQuantity(2)->setGroup($this->group)->setIsActive(true);
        $this->entityManager->persist($this->resource2);

        // A booking that will conflict with our queries
        $this->conflictingBooking = new Booking();
        $this->conflictingBooking
            ->setGroup($this->group)->setAuthor($user)->setTitle('Conflicting Meeting')
            ->setStartAt(new \DateTimeImmutable('tomorrow 10:00'))
            ->setEndAt(new \DateTimeImmutable('tomorrow 11:00'));

        $bookedResource1 = new BookedResource();
        $bookedResource1->setResource($this->resource1)->setQuantity(2); // 2 projectors
        $this->conflictingBooking->addBookedResource($bookedResource1);
        $this->entityManager->persist($this->conflictingBooking);

        // A booking that we will pretend to be updating
        $this->bookingToUpdate = new Booking();
        $this->bookingToUpdate
            ->setGroup($this->group)->setAuthor($user)->setTitle('Booking To Update')
            ->setStartAt(new \DateTimeImmutable('tomorrow 14:00'))
            ->setEndAt(new \DateTimeImmutable('tomorrow 15:00'));

        $bookedResource2 = new BookedResource();
        $bookedResource2->setResource($this->resource1)->setQuantity(1); // 1 projector
        $this->bookingToUpdate->addBookedResource($bookedResource2);
        $this->entityManager->persist($this->bookingToUpdate);

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function test_should_return_all_resources_with_full_quantity_when_no_conflicts(): void
    {
        // Arrange: Query for a time slot far in the future
        $query = new GetResourcesForGroupWithAvailableQuantityQuery(
            $this->group->getId(),
            new \DateTimeImmutable('+1 month 10:00'),
            new \DateTimeImmutable('+1 month 11:00')
        );

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertCount(2, $result);
        $this->assertInstanceOf(ResourceWithAvailableDTO::class, $result[0]);

        $resultById = array_reduce($result, fn($carry, $item) => $carry + [$item->id => $item], []);

        $this->assertEquals(5, $resultById[$this->resource1->getId()]->available_quantity);
        $this->assertEquals(0, $resultById[$this->resource1->getId()]->booked_quantity);

        $this->assertEquals(2, $resultById[$this->resource2->getId()]->available_quantity);
        $this->assertEquals(0, $resultById[$this->resource2->getId()]->booked_quantity);
    }

    public function test_should_return_reduced_quantity_when_conflicting_booking_exists(): void
    {
        // Arrange: Query for a time slot that overlaps with the conflicting booking
        $query = new GetResourcesForGroupWithAvailableQuantityQuery(
            $this->group->getId(),
            new \DateTimeImmutable('tomorrow 10:30'),
            new \DateTimeImmutable('tomorrow 11:30')
        );

        // Act
        $result = ($this->handler)($query);

        // Assert
        $resultById = array_reduce($result, fn($carry, $item) => $carry + [$item->id => $item], []);

        // Resource 1 (Projector): Total 5, Conflicting booking uses 2. Available should be 3.
        $this->assertEquals(3, $resultById[$this->resource1->getId()]->available_quantity);

        // Resource 2 (Whiteboard): Not used by conflicting booking. Available should be 2.
        $this->assertEquals(2, $resultById[$this->resource2->getId()]->available_quantity);
    }

    public function test_should_correctly_calculate_availability_when_updating_booking(): void
    {
        // Arrange: Query for a time slot, also providing the ID of the booking being updated.
        $query = new GetResourcesForGroupWithAvailableQuantityQuery(
            $this->group->getId(),
            new \DateTimeImmutable('tomorrow 14:30'), // Same time as bookingToUpdate
            new \DateTimeImmutable('tomorrow 15:30'),
            $this->bookingToUpdate->getId()
        );

        // Act
        $result = ($this->handler)($query);

        // Assert
        $resultById = array_reduce($result, fn($carry, $item) => $carry + [$item->id => $item], []);

        // Resource 1 (Projector): Total 5. No other bookings conflict. Available should be 5. Booked (by current) should be 1.
        $this->assertEquals(5, $resultById[$this->resource1->getId()]->available_quantity);
        $this->assertEquals(1, $resultById[$this->resource1->getId()]->booked_quantity);
    }
}

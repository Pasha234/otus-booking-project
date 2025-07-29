<?php

namespace App\Tests\Integration\Booking\Query;

use App\Booking\Application\DTO\GetBookingList\BookingDTO;
use App\Booking\Application\Query\GetBookingList\GetBookingListQuery;
use App\Booking\Application\Query\GetBookingList\GetBookingListQueryHandler;
use App\Booking\Domain\Entity\Booking;
use App\Booking\Domain\Entity\Group;
use App\Shared\Application\Exception\NotFoundInRepositoryException;
use App\User\Domain\Entity\User;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GetBookingListTest extends WebTestCase
{
    private Generator $faker;
    private EntityManagerInterface $entityManager;
    private GetBookingListQueryHandler $handler;

    // Test data entities
    private User $user1;
    private User $user2;
    private Group $group1;
    private Booking $bookingInDateRange;
    private Booking $bookingOutOfDateRange;

    public function setUp(): void
    {
        parent::setUp();

        $container = self::getContainer();

        $this->handler = $container->get(GetBookingListQueryHandler::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->faker = Factory::create();

        $purger = new ORMPurger($this->entityManager);
        $purger->purge();

        $this->createTestData();
    }

    private function createTestData(): void
    {
        // Users
        $this->user1 = new User();
        $this->user1->setEmail($this->faker->email())->setFullName('User One')->setPassword('p');
        $this->user2 = new User();
        $this->user2->setEmail($this->faker->email())->setFullName('User Two')->setPassword('p');
        $this->entityManager->persist($this->user1);
        $this->entityManager->persist($this->user2);

        // Group
        $this->group1 = new Group();
        $this->group1->setName('Test Group 1')->setOwner($this->user1);
        $this->entityManager->persist($this->group1);

        // Booking within the target date range
        $this->bookingInDateRange = new Booking();
        $this->bookingInDateRange
            ->setGroup($this->group1)
            ->setAuthor($this->user1)
            ->setTitle('Booking In Range')
            ->setStartAt(new \DateTimeImmutable('tomorrow 10:00'))
            ->setEndAt(new \DateTimeImmutable('tomorrow 11:00'));
        $this->entityManager->persist($this->bookingInDateRange);

        // Booking outside the target date range
        $this->bookingOutOfDateRange = new Booking();
        $this->bookingOutOfDateRange
            ->setGroup($this->group1)
            ->setAuthor($this->user1)
            ->setTitle('Booking Out of Range')
            ->setStartAt(new \DateTimeImmutable('+2 months 10:00'))
            ->setEndAt(new \DateTimeImmutable('+2 months 11:00'));
        $this->entityManager->persist($this->bookingOutOfDateRange);

        // Booking in another group (should not be returned)
        $group2 = new Group();
        $group2->setName('Test Group 2')->setOwner($this->user2);
        $this->entityManager->persist($group2);
        $otherBooking = new Booking();
        $otherBooking
            ->setGroup($group2)
            ->setAuthor($this->user2)
            ->setTitle('Booking in Other Group')
            ->setStartAt(new \DateTimeImmutable('tomorrow 12:00'))
            ->setEndAt(new \DateTimeImmutable('tomorrow 13:00'));
        $this->entityManager->persist($otherBooking);

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function test_should_return_bookings_within_date_range_for_correct_group(): void
    {
        // Arrange
        $startAt = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $endAt = (new \DateTimeImmutable('+1 month'))->format('Y-m-d');
        $query = new GetBookingListQuery($this->group1->getId(), $startAt, $endAt);

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(BookingDTO::class, $result[0]);
        $this->assertEquals($this->bookingInDateRange->getId(), $result[0]->id);
        $this->assertEquals('Booking In Range', $result[0]->title);
    }

    public function test_should_return_empty_array_when_no_bookings_in_range(): void
    {
        // Arrange
        $startAt = (new \DateTimeImmutable('-2 months'))->format('Y-m-d');
        $endAt = (new \DateTimeImmutable('-1 month'))->format('Y-m-d');
        $query = new GetBookingListQuery($this->group1->getId(), $startAt, $endAt);

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_should_set_is_author_flag_correctly(): void
    {
        // Arrange
        $startAt = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $endAt = (new \DateTimeImmutable('+1 month'))->format('Y-m-d');

        // Case 1: User is the author
        $queryForAuthor = new GetBookingListQuery($this->group1->getId(), $startAt, $endAt, $this->user1->getId());
        $resultForAuthor = ($this->handler)($queryForAuthor);
        $this->assertTrue($resultForAuthor[0]->is_author);

        // Case 2: User is NOT the author
        $queryForNonAuthor = new GetBookingListQuery($this->group1->getId(), $startAt, $endAt, $this->user2->getId());
        $resultForNonAuthor = ($this->handler)($queryForNonAuthor);
        $this->assertFalse($resultForNonAuthor[0]->is_author);
    }

    public function test_should_throw_exception_when_user_not_found(): void
    {
        // Arrange
        $nonExistentUserId = '00000000-0000-0000-0000-000000000000';
        $query = new GetBookingListQuery($this->group1->getId(), '2023-01-01', '2023-01-31', $nonExistentUserId);

        // Assert
        $this->expectException(NotFoundInRepositoryException::class);

        // Act
        ($this->handler)($query);
    }
}
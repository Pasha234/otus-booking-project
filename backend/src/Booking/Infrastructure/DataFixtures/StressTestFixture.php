<?php

namespace App\Booking\Infrastructure\DataFixtures;

use App\Booking\Domain\Entity\BookedResource;
use App\Booking\Domain\Entity\Booking;
use App\Booking\Domain\Entity\Group;
use App\Booking\Domain\Entity\GroupParticipant;
use App\Booking\Domain\Entity\Resource;
use App\Shared\Infrastructure\Tools\WithFaker;
use App\User\Domain\Entity\User;
use Doctrine\DBAL\Connection;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class StressTestFixture extends Fixture implements FixtureGroupInterface
{
    use WithFaker;

    private const NUM_PARTICIPANTS = 10;
    private const NUM_RESOURCES = 10;
    private const NUM_BOOKINGS = 10000;
    private const START_DATE = '2025-07-29';
    private const DATE_INTERVAL = '+3 months';

    private Connection $connection;
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(Connection $connection, UserPasswordHasherInterface $user_password_hasher)
    {
        $this->connection = $connection;
        $this->userPasswordHasher = $user_password_hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadData($manager);
    }

    public function loadData(ObjectManager $manager): void
    {
        ini_set('memory_limit', '1024M'); // Increase memory limit

        // Create a user
        $user = new User();
        $user->setEmail('matkinpasha3@gmail.com')->setFullName($this->faker()->name());
        $user->setPassword($this->userPasswordHasher->hashPassword($user, 'password'));
        $manager->persist($user);
        $manager->flush();

        // Create a group
        $group = new Group();
        $group->setName($this->faker()->company())->setDescription($this->faker()->sentence())->setOwner($user);
        $manager->persist($group);

        // Create participants
        $participants = [];
        for ($i = 0; $i < self::NUM_PARTICIPANTS; $i++) {
            $participantUser = new User();
            $participantUser->setEmail($this->faker()->email())->setFullName($this->faker()->name())->setPassword('password');
            $manager->persist($participantUser);
            $participant = new GroupParticipant();
            $participant->setUser($participantUser)->setGroup($group);
            $manager->persist($participant);
            $participants[] = $participantUser;
        }

        // Create resources
        $resources = [];
        for ($i = 0; $i < self::NUM_RESOURCES; $i++) {
            $resource = new Resource();
            $resource->setName($this->faker()->word())->setQuantity($this->faker()->numberBetween(1, 20))->setGroup($group)->setIsActive(true);
            $manager->persist($resource);
            $resources[] = $resource;
        }

        $manager->flush();
        $manager->clear();

        // Create bookings in batches
        $batchSize = 50; // Define a batch size
        $startDate = new DateTimeImmutable(self::START_DATE);
        $endDate = $startDate->modify(self::DATE_INTERVAL);

        for ($i = 0; $i < self::NUM_BOOKINGS; $i++) {
            $booking = new Booking();
            $bookingId = $this->faker()->uuid();
            $booking->setTitle($this->faker()->sentence(3))->setDescription($this->faker()->paragraph(2));

            $randomDate = $this->faker()->dateTimeBetween($startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
            $startAt = DateTimeImmutable::createFromMutable($randomDate);
            $endAt = DateTimeImmutable::createFromMutable($this->faker()->dateTimeBetween($startAt->format('Y-m-d'), $endDate->format('Y-m-d')));

            $startAtStr = $startAt->format('Y-m-d H:i:s');
            $endAtStr = $endAt->format('Y-m-d H:i:s');

            $this->connection->executeStatement(
                'INSERT INTO booking (id, author_id, group_id, title, description, start_at, end_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $bookingId,
                    $user->getId(),
                    $group->getId(),
                    $booking->getTitle(),
                    $booking->getDescription(),
                    $startAtStr,
                    $endAtStr,
                ]
            );

            // Add random participants
            $numParticipants = $this->faker()->numberBetween(0, \count($participants));
            shuffle($participants);
            for ($j = 0; $j < $numParticipants; $j++) {
                $participant = $participants[$j];
                $this->connection->executeStatement(
                    'INSERT INTO booking_user (booking_id, user_id) VALUES (?, ?)',
                    [
                        $bookingId,
                        $participant->getId(),
                    ]
                );
            }

            // Add random resources
            $numResources = $this->faker()->numberBetween(0, \count($resources));
            shuffle($resources);
            for ($k = 0; $k < $numResources; $k++) {
                $resource = $resources[$k];
                $quantity = min($this->faker()->numberBetween(1, 5), $resource->getQuantity());
                $this->connection->executeStatement(
                    'INSERT INTO booked_resource (booking_id, resource_id, quantity, id) VALUES (?, ?, ?, ?)',
                    [
                        $bookingId,
                        $resource->getId(),
                        $quantity,
                        $this->faker()->uuid(),
                    ]
                );
            }

            // Flush and clear in batches
            if (($i % $batchSize) === 0) {
                $manager->flush();
                $manager->clear();
            }
        }

        // Final flush and clear
        $manager->flush();
        $manager->clear();
    }


    public static function getGroups(): array
    {
        return ['stress-group'];
    }
}
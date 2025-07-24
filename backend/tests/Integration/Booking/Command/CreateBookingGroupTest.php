<?php

namespace App\Tests\Integration\Booking\Command;

use App\Booking\Application\Command\CreateBookingGroup\CreateBookingGroupCommand;
use App\Booking\Application\Command\CreateBookingGroup\CreateBookingGroupCommandHandler;
use App\Booking\Domain\Repository\GroupRepositoryInterface;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Exception\NotFoundInRepositoryException;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CreateBookingGroupTest extends WebTestCase
{
    private Generator $faker;
    private GroupRepositoryInterface $groupRepository;
    private ORMExecutor $executor;
    private EntityManagerInterface $entityManager;
    private CreateBookingGroupCommandHandler $handler;

    public function setUp(): void
    {
        parent::setUp();

        $container = self::getContainer();
        
        $this->handler = $container->get(CreateBookingGroupCommandHandler::class);

        $this->faker = Factory::create();
        $this->groupRepository = $container->get(GroupRepositoryInterface::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);

        $loader = new Loader();
        // We only need a user to act as the group owner
        $loader->addFixture($container->get(UserFixtures::class));

        $this->executor = new ORMExecutor($this->entityManager, new ORMPurger());
        $this->executor->execute($loader->getFixtures());
    }

    public function test_should_create_booking_group(): void
    {
        // Arrange
        /** @var User $owner */
        $owner = $this->executor->getReferenceRepository()->getReference('user', User::class);

        $groupName = $this->faker->company();
        $groupDescription = $this->faker->sentence();

        $command = new CreateBookingGroupCommand(
            $groupName,
            $groupDescription,
            $owner->getEmail()
        );

        // Act
        $groupId = ($this->handler)($command);

        // Assert
        $this->entityManager->clear(); // Ensure we fetch a fresh entity from the DB
        $group = $this->groupRepository->getById($groupId);

        $this->assertNotNull($group);
        $this->assertEquals($groupId, $group->getId());
        $this->assertEquals($groupName, $group->getName());
        $this->assertEquals($groupDescription, $group->getDescription());
        $this->assertEquals($owner->getId(), $group->getOwner()->getId());
    }

    public function test_should_throw_exception_when_owner_not_found(): void
    {
        // Arrange
        $command = new CreateBookingGroupCommand('Some Group', 'Some Desc', 'non-existent@user.com');

        // Assert
        $this->expectException(NotFoundInRepositoryException::class);
        $this->expectExceptionMessage('Owner not found');

        // Act
        ($this->handler)($command);
    }
}
<?php

namespace App\Tests\Integration\Booking\Query;

use App\Booking\Application\DTO\Basic\GroupDTO;
use App\Booking\Application\Query\FindBookingGroupsByUser\FindBookingGroupsByUserQuery;
use App\Booking\Application\Query\FindBookingGroupsByUser\FindBookingGroupsByUserQueryHandler;
use App\Booking\Domain\Entity\Group;
use App\Booking\Domain\Entity\GroupParticipant;
use App\Booking\Infrastructure\DataFixtures\GroupFixture;
use App\Shared\Application\Exception\NotFoundInRepositoryException;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\DataFixtures\SecondUserFixtures;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

class FindBookingGroupsByUserTest extends WebTestCase
{
    private FindBookingGroupsByUserQueryHandler $handler;
    private ORMExecutor $executor;
    private EntityManagerInterface $entityManager;

    public function setUp(): void
    {
        parent::setUp();
        $container = self::getContainer();

        $this->handler = $container->get(FindBookingGroupsByUserQueryHandler::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);

        $loader = new Loader();
        // We need users and a group for our tests
        $loader->addFixture($container->get(UserFixtures::class));
        $loader->addFixture($container->get(SecondUserFixtures::class));
        $loader->addFixture($container->get(GroupFixture::class));

        $this->executor = new ORMExecutor($this->entityManager, new ORMPurger());
        $this->executor->execute($loader->getFixtures());
    }

    public function test_should_find_groups_for_user_who_is_participant(): void
    {
        // Arrange
        /** @var Group $group */
        $group = $this->executor->getReferenceRepository()->getReference('group', Group::class);
        /** @var User $user */
        $user = $this->executor->getReferenceRepository()->getReference('user', User::class);

        // Manually make the user a participant in the group
        $participant = (new GroupParticipant())
            ->setGroup($group)
            ->setUser($user);
        $this->entityManager->persist($participant);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $query = new FindBookingGroupsByUserQuery($user->getId());

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(GroupDTO::class, $result[0]);
        $this->assertEquals($group->getId(), $result[0]->id);
        $this->assertEquals($group->getName(), $result[0]->name);
    }

    public function test_should_return_empty_array_for_user_with_no_groups(): void
    {
        // Arrange
        /** @var User $userWithNoGroups */
        $userWithNoGroups = $this->executor->getReferenceRepository()->getReference(SecondUserFixtures::SECOND_USER_REFERENCE, User::class);
        $query = new FindBookingGroupsByUserQuery($userWithNoGroups->getId());

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_should_throw_exception_if_user_not_found(): void
    {
        // Arrange
        $query = new FindBookingGroupsByUserQuery(Uuid::v4()->toRfc4122());

        // Assert
        $this->expectException(NotFoundInRepositoryException::class);
        $this->expectExceptionMessage('User not found');

        // Act
        ($this->handler)($query);
    }
}
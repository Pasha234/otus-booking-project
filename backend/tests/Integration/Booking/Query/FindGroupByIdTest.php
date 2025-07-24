<?php

namespace App\Tests\Integration\Booking\Query;

use Symfony\Component\Uid\Uuid;
use App\User\Domain\Entity\User;
use App\Booking\Domain\Entity\Group;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\ORM\EntityManagerInterface;
use App\Booking\Domain\Entity\GroupParticipant;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use App\Booking\Infrastructure\DataFixtures\GroupFixture;
use App\User\Infrastructure\DataFixtures\SecondUserFixtures;
use App\Booking\Application\Query\FindGroupById\FindGroupByIdQuery;
use App\Booking\Application\Query\FindGroupById\FindGroupByIdQueryHandler;
use App\Booking\Application\DTO\FindGroupById\Response as GroupResponseDTO;

class FindGroupByIdTest extends WebTestCase
{
    private FindGroupByIdQueryHandler $handler;
    private ORMExecutor $executor;
    private EntityManagerInterface $entityManager;

    public function setUp(): void
    {
        parent::setUp();
        $container = self::getContainer();

        $this->handler = $container->get(FindGroupByIdQueryHandler::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);

        $loader = new Loader();
        // We need users and a group for our tests
        $loader->addFixture($container->get(UserFixtures::class));
        $loader->addFixture($container->get(SecondUserFixtures::class));
        $loader->addFixture($container->get(GroupFixture::class));

        $this->executor = new ORMExecutor($this->entityManager, new ORMPurger());
        $this->executor->execute($loader->getFixtures());
    }

    public function test_should_find_group_by_id_without_user_check(): void
    {
        // Arrange
        /** @var Group $group */
        $group = $this->executor->getReferenceRepository()->getReference('group', Group::class);
        $query = new FindGroupByIdQuery($group->getId());

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertNotNull($result);
        $this->assertInstanceOf(GroupResponseDTO::class, $result);
        $this->assertEquals($group->getId(), $result->id);
        $this->assertEquals($group->getName(), $result->name);
    }

    public function test_should_find_group_when_user_is_in_group(): void
    {
        // Arrange
        /** @var Group $group */
        $group = $this->executor->getReferenceRepository()->getReference('group', Group::class);
        /** @var User $participant */
        $participant = $this->executor->getReferenceRepository()->getReference(SecondUserFixtures::SECOND_USER_REFERENCE, User::class);

        // Manually make the user a participant
        $group->addGroupParticipant((new GroupParticipant())
            ->setGroup($group)
            ->setUser($participant)
        );
        $this->entityManager->flush();
        $this->entityManager->clear();

        $query = new FindGroupByIdQuery($group->getId(), $participant->getId());

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertNotNull($result);
        $this->assertInstanceOf(GroupResponseDTO::class, $result);
        $this->assertEquals($group->getId(), $result->id);
    }

    public function test_should_return_null_when_group_not_found(): void
    {
        // Arrange
        $query = new FindGroupByIdQuery(Uuid::v4()->toRfc4122());

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertNull($result);
    }

    public function test_should_return_null_when_user_is_not_in_group(): void
    {
        // Arrange
        /** @var Group $group */
        $group = $this->executor->getReferenceRepository()->getReference('group', Group::class);
        /** @var User $nonParticipant */
        $nonParticipant = $this->executor->getReferenceRepository()->getReference(SecondUserFixtures::SECOND_USER_REFERENCE, User::class);

        $query = new FindGroupByIdQuery($group->getId(), $nonParticipant->getId());

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertNull($result);
    }

    public function test_should_return_null_when_user_is_not_found(): void
    {
        // Arrange
        /** @var Group $group */
        $group = $this->executor->getReferenceRepository()->getReference('group', Group::class);
        $nonExistentUserId = Uuid::v4()->toRfc4122();

        $query = new FindGroupByIdQuery($group->getId(), $nonExistentUserId);

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertNull($result);
    }
}
<?php

namespace App\Tests\Integration\Booking\Query;

use App\Booking\Application\DTO\Basic\InvitationUserDTO;
use App\Booking\Application\Query\SearchUsersForInvitations\SearchUsersForInvitationsQuery;
use App\Booking\Application\Query\SearchUsersForInvitations\SearchUsersForInvitationsQueryHandler;
use App\Booking\Domain\Entity\Group;
use App\Booking\Domain\Entity\GroupParticipant;
use App\Booking\Infrastructure\DataFixtures\GroupFixture;
use App\Shared\Application\Exception\NotFoundInRepositoryException;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\DataFixtures\SecondUserFixtures;
use App\User\Infrastructure\DataFixtures\ThirdUserFixtures;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

class SearchUsersForInvitationsTest extends WebTestCase
{
    private SearchUsersForInvitationsQueryHandler $handler;
    private ORMExecutor $executor;
    private EntityManagerInterface $entityManager;

    public function setUp(): void
    {
        parent::setUp();
        $container = self::getContainer();

        $this->handler = $container->get(SearchUsersForInvitationsQueryHandler::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);

        $loader = new Loader();
        $loader->addFixture($container->get(UserFixtures::class));
        $loader->addFixture($container->get(SecondUserFixtures::class));
        $loader->addFixture($container->get(ThirdUserFixtures::class));
        $loader->addFixture($container->get(GroupFixture::class));

        $this->executor = new ORMExecutor($this->entityManager, new ORMPurger());
        $this->executor->execute($loader->getFixtures());
    }

    public function test_should_find_users_not_in_group(): void
    {
        // Arrange
        /** @var Group $group */
        $group = $this->executor->getReferenceRepository()->getReference('group', Group::class);
        /** @var User $searchableUser */
        $searchableUser = $this->executor->getReferenceRepository()->getReference(ThirdUserFixtures::THIRD_USER_REFERENCE, User::class);

        $query = new SearchUsersForInvitationsQuery(
            $group->getId(), 
            $searchableUser->getEmail(),
            10
        );

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(InvitationUserDTO::class, $result[0]);
        $this->assertEquals($searchableUser->getId(), $result[0]->id);
        $this->assertEquals($searchableUser->getFullName(), $result[0]->full_name);
    }

    public function test_should_not_find_users_already_in_group(): void
    {
        // Arrange
        /** @var Group $group */
        $group = $this->executor->getReferenceRepository()->getReference('group', Group::class);
        /** @var User $alreadyMember */
        $alreadyMember = $this->executor->getReferenceRepository()->getReference(SecondUserFixtures::SECOND_USER_REFERENCE, User::class);

        // Manually make the user a participant in the group
        $group->addGroupParticipant((new GroupParticipant)
            ->setGroup($group)
            ->setUser($alreadyMember)
        );
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Search for the user who is now a member
        $query = new SearchUsersForInvitationsQuery(
            $group->getId(),
            $alreadyMember->getEmail(),
            10
        );

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_should_return_empty_array_for_non_matching_query(): void
    {
        // Arrange
        /** @var Group $group */
        $group = $this->executor->getReferenceRepository()->getReference('group', Group::class);
        $query = new SearchUsersForInvitationsQuery(
            $group->getId(),
            'nonexistent-user-string',
            10
        );

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_should_throw_exception_if_group_not_found(): void
    {
        // Arrange
        $nonExistentGroupId = Uuid::v4()->toRfc4122();
        $query = new SearchUsersForInvitationsQuery(
            $nonExistentGroupId,
            'any',
            10,
        );

        // Assert
        $this->expectException(NotFoundInRepositoryException::class);
        $this->expectExceptionMessage('Group not found');

        // Act
        ($this->handler)($query);
    }
}
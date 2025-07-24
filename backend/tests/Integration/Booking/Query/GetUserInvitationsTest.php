<?php

namespace App\Tests\Integration\Booking\Query;

use App\Booking\Application\Query\GetUserInvitations\GetUserInvitationsQuery;
use App\Booking\Application\Query\GetUserInvitations\GetUserInvitationsQueryHandler;
use App\Booking\Domain\Entity\Group;
use App\Booking\Infrastructure\DataFixtures\GroupFixture;
use App\User\Application\DTO\InvitationDTO;
use App\User\Domain\Entity\Invitation;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\InvitationStatus;
use App\User\Infrastructure\DataFixtures\SecondUserFixtures;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GetUserInvitationsTest extends WebTestCase
{
    private GetUserInvitationsQueryHandler $handler;
    private ORMExecutor $executor;
    private EntityManagerInterface $entityManager;

    public function setUp(): void
    {
        parent::setUp();
        $container = self::getContainer();

        $this->handler = $container->get(GetUserInvitationsQueryHandler::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);

        $loader = new Loader();
        $loader->addFixture($container->get(UserFixtures::class));
        $loader->addFixture($container->get(SecondUserFixtures::class));
        $loader->addFixture($container->get(GroupFixture::class));

        $this->executor = new ORMExecutor($this->entityManager, new ORMPurger());
        $this->executor->execute($loader->getFixtures());
    }

    public function test_should_find_pending_invitations_for_user(): void
    {
        // Arrange
        /** @var Group $group */
        $group = $this->executor->getReferenceRepository()->getReference('group', Group::class);
        /** @var User $inviter */
        $inviter = $this->executor->getReferenceRepository()->getReference('user', User::class);
        /** @var User $invitedUser */
        $invitedUser = $this->executor->getReferenceRepository()->getReference(SecondUserFixtures::SECOND_USER_REFERENCE, User::class);

        // Create a pending invitation that should be found
        $pendingInvitation = new Invitation();
        $pendingInvitation->setInvitee($inviter);
        $pendingInvitation->setInvitedEmail($invitedUser->getEmail());
        $pendingInvitation->setGroup($group);
        $pendingInvitation->setStatus(InvitationStatus::PENDING);
        $this->entityManager->persist($pendingInvitation);

        // Create an accepted invitation that should be ignored
        $acceptedInvitation = new Invitation();
        $acceptedInvitation->setInvitee($inviter);
        $acceptedInvitation->setInvitedEmail($invitedUser->getEmail());
        $acceptedInvitation->setGroup($group);
        $acceptedInvitation->setStatus(InvitationStatus::ACCEPTED);
        $this->entityManager->persist($acceptedInvitation);

        $this->entityManager->flush();
        $this->entityManager->clear();

        $query = new GetUserInvitationsQuery($invitedUser->getEmail());

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(InvitationDTO::class, $result[0]);
        $this->assertEquals($pendingInvitation->getId(), $result[0]->id);
        $this->assertEquals($group->getName(), $result[0]->group_name);
        $this->assertEquals($inviter->getFullName(), $result[0]->invitee_name);
    }

    public function test_should_return_empty_array_for_user_with_no_invitations(): void
    {
        // Arrange
        /** @var User $userWithNoInvitations */
        $userWithNoInvitations = $this->executor->getReferenceRepository()->getReference(SecondUserFixtures::SECOND_USER_REFERENCE, User::class);
        $query = new GetUserInvitationsQuery($userWithNoInvitations->getEmail());

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
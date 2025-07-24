<?php

namespace App\Tests\Integration\Booking\Command;

use App\Booking\Application\Command\InviteUserToGroup\InviteUserToGroupCommand;
use App\Booking\Application\Command\InviteUserToGroup\InviteUserToGroupCommandHandler;
use App\Booking\Domain\Entity\Group;
use App\Booking\Domain\Entity\GroupParticipant;
use App\Booking\Infrastructure\DataFixtures\GroupFixture;
use App\Shared\Application\Exception\NotFoundInRepositoryException;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserAlreadyHasInvitationException;
use App\User\Domain\Exception\UserAlreadyInGroupException;
use App\User\Domain\Repository\InvitationRepositoryInterface;
use App\User\Infrastructure\DataFixtures\SecondUserFixtures;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class InviteUserToGroupTest extends WebTestCase
{
    private InviteUserToGroupCommandHandler $handler;
    private InvitationRepositoryInterface $invitationRepository;
    private ORMExecutor $executor;
    private EntityManagerInterface $entityManager;

    public function setUp(): void
    {
        parent::setUp();
        $container = self::getContainer();

        $this->handler = $container->get(InviteUserToGroupCommandHandler::class);
        $this->invitationRepository = $container->get(InvitationRepositoryInterface::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);

        $loader = new Loader();
        // We need an owner, a group, and a user to invite
        $loader->addFixture($container->get(UserFixtures::class));
        $loader->addFixture($container->get(SecondUserFixtures::class));
        $loader->addFixture($container->get(GroupFixture::class));

        $this->executor = new ORMExecutor($this->entityManager, new ORMPurger());
        $this->executor->execute($loader->getFixtures());
    }

    public function test_should_create_invitation(): void
    {
        // Arrange
        /** @var Group $group */
        $group = $this->executor->getReferenceRepository()->getReference('group', Group::class);
        /** @var User $owner */
        $owner = $this->executor->getReferenceRepository()->getReference('user', User::class);
        /** @var User $invitedUser */
        $invitedUser = $this->executor->getReferenceRepository()->getReference(SecondUserFixtures::SECOND_USER_REFERENCE, User::class);

        $command = new InviteUserToGroupCommand(
            $group->getId(),
            $invitedUser->getEmail(),
            $owner->getId(),
        );

        // Act
        ($this->handler)($command);

        // Assert
        $this->entityManager->clear();
        $invitation = $this->invitationRepository->findOneBy(['invited_email' => $invitedUser->getEmail()]);

        $this->assertNotNull($invitation);
        $this->assertEquals($group->getId(), $invitation->getGroup()->getId());
        $this->assertEquals($owner->getId(), $invitation->getInvitee()->getId());
        $this->assertEquals('pending', $invitation->getStatus()->value);
    }

    public function test_should_throw_when_inviter_is_not_owner(): void
    {
        // Arrange
        /** @var Group $group */
        $group = $this->executor->getReferenceRepository()->getReference('group', Group::class);
        /** @var User $notTheOwner */
        $notTheOwner = $this->executor->getReferenceRepository()->getReference(SecondUserFixtures::SECOND_USER_REFERENCE, User::class);

        $command = new InviteUserToGroupCommand(
            $group->getId(),
            'some.other.user@test.com',
            $notTheOwner->getId(),
        );

        // Assert
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Only the group owner can invite users.');

        // Act
        ($this->handler)($command);
    }

    public function test_should_throw_when_invited_user_not_found(): void
    {
        // Arrange
        /** @var Group $group */
        $group = $this->executor->getReferenceRepository()->getReference('group', Group::class);
        /** @var User $owner */
        $owner = $this->executor->getReferenceRepository()->getReference('user', User::class);

        $command = new InviteUserToGroupCommand(
            $group->getId(),
            'non-existent.user@test.com',
            $owner->getId(),
        );

        // Assert
        $this->expectException(NotFoundInRepositoryException::class);
        $this->expectExceptionMessage('User to invite not found.');

        // Act
        ($this->handler)($command);
    }

    public function test_should_throw_when_user_already_in_group(): void
    {
        // Arrange
        /** @var Group $group */
        $group = $this->executor->getReferenceRepository()->getReference('group', Group::class);
        /** @var User $owner */
        $owner = $this->executor->getReferenceRepository()->getReference('user', User::class);
        /** @var User $invitedUser */
        $invitedUser = $this->executor->getReferenceRepository()->getReference(SecondUserFixtures::SECOND_USER_REFERENCE, User::class);

        // Manually add user to the group
        $group->addGroupParticipant((new GroupParticipant())
            ->setGroup($group)
            ->setUser($invitedUser)
        );
        $this->entityManager->flush();
        $this->entityManager->clear();

        $command = new InviteUserToGroupCommand(
            $group->getId(),
            $invitedUser->getEmail(),
            $owner->getId(),
        );

        // Assert
        $this->expectException(UserAlreadyInGroupException::class);
        $this->expectExceptionMessage('User already in that group');

        // Act
        ($this->handler)($command);
    }

    public function test_should_throw_when_user_already_has_invitation(): void
    {
        // Arrange
        /** @var Group $group */
        $group = $this->executor->getReferenceRepository()->getReference('group', Group::class);
        /** @var User $owner */
        $owner = $this->executor->getReferenceRepository()->getReference('user', User::class);
        /** @var User $invitedUser */
        $invitedUser = $this->executor->getReferenceRepository()->getReference(SecondUserFixtures::SECOND_USER_REFERENCE, User::class);

        $command = new InviteUserToGroupCommand(
            $group->getId(),
            $invitedUser->getEmail(),
            $owner->getId(),
        );

        // Send the first invitation
        ($this->handler)($command);
        $this->entityManager->clear();

        // Assert
        $this->expectException(UserAlreadyHasInvitationException::class);
        $this->expectExceptionMessage('An invitation has already been sent to this user.');

        // Act: try to send it again
        ($this->handler)($command);
    }
}
<?php

namespace App\Booking\Application\Command\InviteUserToGroup;

use App\Booking\Domain\Repository\GroupRepositoryInterface;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\Shared\Application\Exception\NotFoundInRepositoryException;
use App\User\Domain\Entity\Invitation;
use App\User\Domain\Enum\InvitationStatus;
use App\User\Domain\Exception\UserAlreadyHasInvitationException;
use App\User\Domain\Exception\UserAlreadyInGroupException;
use App\User\Domain\Repository\InvitationRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class InviteUserToGroupCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly InvitationRepositoryInterface $invitationRepository,
    ) {
    }

    public function __invoke(InviteUserToGroupCommand $command): void
    {
        $group = $this->groupRepository->getById($command->groupId);
        if (!$group) {
            throw new NotFoundInRepositoryException('Group not found.');
        }

        $inviter = $this->userRepository->getById($command->inviterId);
        if (!$inviter) {
            throw new NotFoundInRepositoryException('Inviter not found.');
        }

        if ($group->getOwner() !== $inviter) {
            throw new AccessDeniedException('Only the group owner can invite users.');
        }

        $invitedUser = $this->userRepository->findOneBy(['email' => $command->invitedUserEmail]);
        if (!$invitedUser) {
            throw new NotFoundInRepositoryException('User to invite not found.');
        }

        if ($group->checkUserIsInGroup($invitedUser)) {
            throw new UserAlreadyInGroupException('User already in that group');
        }

        if ($invitedUser->hasPendingInvitation($group)) {
            throw new UserAlreadyHasInvitationException('An invitation has already been sent to this user.');
        }

        $invitation = new Invitation();
        $invitation->setGroup($group);
        $invitation->setInvitee($inviter);
        $invitation->setInvitedEmail($command->invitedUserEmail);
        $invitation->setStatus(InvitationStatus::PENDING);
        $invitation->setExpiresAt((new \DateTimeImmutable())->modify('+7 days'));

        $this->invitationRepository->save($invitation);
    }
}
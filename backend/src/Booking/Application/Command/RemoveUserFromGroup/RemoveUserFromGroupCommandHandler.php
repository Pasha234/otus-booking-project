<?php

namespace App\Booking\Application\Command\RemoveUserFromGroup;

use App\User\Domain\Entity\Invitation;
use App\User\Domain\Enum\InvitationStatus;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Exception\UserAlreadyInGroupException;
use App\Booking\Domain\Repository\GroupRepositoryInterface;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\User\Domain\Exception\UserAlreadyHasInvitationException;
use App\Shared\Application\Exception\NotFoundInRepositoryException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Booking\Domain\Repository\GroupParticipantRepositoryInterface;

class RemoveUserFromGroupCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly GroupParticipantRepositoryInterface $groupParticipantRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(RemoveUserFromGroupCommand $command): void
    {
        $participant = $this->groupParticipantRepository->findOneBy([
            'user' => $command->userId,
            'group' => $command->groupId,
        ]);

        if (!$participant) {
            throw new NotFoundInRepositoryException('Participant not found.');
        }

        $admin = $this->userRepository->getById($command->adminId);
        if (!$admin) {
            throw new NotFoundInRepositoryException('Admin not found.');
        }

        if ($participant->getGroup()->getOwner() !== $admin) {
            throw new AccessDeniedException('Only the group owner can invite users.');
        }

        $this->groupParticipantRepository->delete($participant);
    }
}
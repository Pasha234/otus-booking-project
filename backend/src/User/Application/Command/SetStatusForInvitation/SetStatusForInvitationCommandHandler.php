<?php

namespace App\User\Application\Command\SetStatusForInvitation;

use App\User\Domain\Service\GroupInvitationService;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\User\Domain\Repository\InvitationRepositoryInterface;
use App\Shared\Application\Exception\NotFoundInRepositoryException;

class SetStatusForInvitationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private InvitationRepositoryInterface $invitationReadRepository,
        private GroupInvitationService $groupInvitationService,
    )
    {
        
    }

    public function __invoke(SetStatusForInvitationCommand $command): void
    {
        $user = $this->userRepository->getById($command->user_id);

        if (!$user) {
            throw new NotFoundInRepositoryException('User not found');
        }

        $invitation = $this->invitationReadRepository->findOneBy([
            'id' => $command->invitation_id,
            'invited_email' => $user->getEmail(),
        ]);

        if (!$invitation) {
            throw new NotFoundInRepositoryException('Invitation not found');
        }

        if ($command->is_accepted) {
            $this->groupInvitationService->acceptInvitation($invitation, $user);
        } else {
            $this->groupInvitationService->declineInvitation($invitation);
        }
    }
}
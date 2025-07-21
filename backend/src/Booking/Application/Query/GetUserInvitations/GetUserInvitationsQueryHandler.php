<?php

namespace App\Booking\Application\Query\GetUserInvitations;

use App\User\Domain\Enum\InvitationStatus;
use App\User\Domain\Entity\Invitation;
use App\User\Application\DTO\InvitationDTO;
use App\Shared\Application\Query\QueryHandlerInterface;
use App\User\Domain\Repository\InvitationReadRepositoryInterface;

class GetUserInvitationsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private InvitationReadRepositoryInterface $invitationReadRepository
    )
    {
        
    }

    public function __invoke(GetUserInvitationsQuery $query): array
    {
        $results = $this->invitationReadRepository->findBy([
            'invited_email' => $query->user_email,
            'status' => InvitationStatus::PENDING,
        ]);

        return array_map(function(Invitation $invitation) {
            return InvitationDTO::fromEntity($invitation);
        }, $results);
    }
}
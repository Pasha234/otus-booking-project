<?php

namespace App\Booking\Application\Query\SearchUsersForInvitations;

use App\Booking\Application\DTO\Basic\InvitationUserDTO;
use App\Booking\Domain\Entity\GroupParticipant;
use App\Booking\Domain\Repository\GroupRepositoryInterface;
use App\Shared\Application\Exception\NotFoundInRepositoryException;
use App\Shared\Application\Query\QueryHandlerInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserReadRepositoryInterface;

class SearchUsersForInvitationsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        public GroupRepositoryInterface $groupRepository,
        public UserReadRepositoryInterface $userReadRepository,
    )
    {
        
    }

    public function __invoke(SearchUsersForInvitationsQuery $query): array
    {
        $group = $this->groupRepository->getById($query->group_id);

        if (!$group) {
            throw new NotFoundInRepositoryException('Group not found');
        }

        $exclude_ids = $group->getMembers()->map(function(GroupParticipant $groupParticipant) {
            return $groupParticipant->getUser()->getId();
        })->toArray();

        return array_map(function(User $user) use ($group) {
            return InvitationUserDTO::fromEntity($user, $group);
        }, $this->userReadRepository->search($query->query, $exclude_ids));
    }
}
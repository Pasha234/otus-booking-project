<?php

namespace App\Booking\Application\Query\FindBookingGroupsByUser;

use App\Booking\Application\DTO\Basic\GroupDTO;
use App\Booking\Domain\Entity\Group;
use App\Booking\Domain\Repository\GroupRepositoryInterface;
use App\Shared\Application\Exception\NotFoundInRepositoryException;
use App\Shared\Application\Query\QueryHandlerInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

class FindBookingGroupsByUserQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly GroupRepositoryInterface $bookingGroupRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    public function __invoke(FindBookingGroupsByUserQuery $query): array
    {
        $user = $this->userRepository->findOneBy(['id' => $query->user_id]);

        if (!$user) {
            throw new NotFoundInRepositoryException('User not found');
        }

        $bookingGroups = $this->bookingGroupRepository->findByParticipantId($user->getId());

        return array_map(function(Group $group) {
            return GroupDTO::fromEntity($group);
        }, $bookingGroups);
    }
}
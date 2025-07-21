<?php

namespace App\Booking\Application\Query\FindGroupById;

use App\Booking\Application\DTO\FindGroupById\Response;
use App\Booking\Domain\Repository\GroupRepositoryInterface;
use App\Shared\Application\Query\QueryHandlerInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

class FindGroupByIdQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly GroupRepositoryInterface $bookingGroupRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    public function __invoke(FindGroupByIdQuery $query): ?Response
    {
        $group = $this->bookingGroupRepository->getById($query->id);

        if (!$group) {
            return null;
        }

        if ($query->userId) {
            $user = $this->userRepository->getById($query->userId);

            if (!$user || !$group->checkUserIsInGroup($user)) {
                return null;
            }
        }

        return Response::fromEntity($group);
    }
}
<?php

namespace App\User\Application\Query\SearchUsers;

use App\User\Domain\Entity\User;
use App\User\Application\DTO\UserDTO;
use App\Shared\Application\Query\QueryHandlerInterface;
use App\User\Domain\Repository\UserReadRepositoryInterface;

class SearchUsersQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private UserReadRepositoryInterface $userReadRepository
    )
    {
        
    }

    public function __invoke(SearchUsersQuery $query): array
    {
        $results = $this->userReadRepository->search($query->query);

        return array_map(function(User $user) {
            return UserDTO::fromEntity($user);
        }, $results);
    }
}
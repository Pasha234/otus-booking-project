<?php

namespace App\User\Application\Query\FindUsersByEmail;

use App\Shared\Application\Query\QueryHandlerInterface;
use App\User\Domain\Entity\User;
use App\User\Application\DTO\UserDTO;
use App\User\Domain\Repository\UserRepositoryInterface;

class FindUsersByEmailQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    )
    {
        
    }

    /**
     * @return UserDTO[]
     */
    public function __invoke(FindUsersByEmailQuery $query): array
    {
        $users = $this->userRepository->findBy([
            'email' => $query->email
        ]);

        return array_map(function(User $user) {
            return UserDTO::fromEntity($user);
        }, $users);
    }
}
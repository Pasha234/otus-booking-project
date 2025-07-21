<?php

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use App\User\Domain\Repository\UserReadRepositoryInterface;
use App\Shared\Infrastructure\Repository\BaseDbReadRepository;

class UserDbReadRepository extends BaseDbReadRepository implements UserReadRepositoryInterface
{
    public function getEntityClass(): string
    {
        return User::class;
    }

    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry);
    }

    public function search(string $query, array $exclude_ids = [], int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('u');

        return $qb
            ->where($qb->expr()->orX(
                'u.full_name LIKE :query',
                'u.email LIKE :query'
            ))
            ->andWhere($qb->expr()->notIn('u.id', $exclude_ids))
            ->setParameter('query', "%{$query}%")
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
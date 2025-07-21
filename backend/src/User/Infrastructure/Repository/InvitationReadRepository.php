<?php

namespace App\User\Infrastructure\Repository;

use App\Shared\Infrastructure\Repository\BaseDbReadRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\User\Domain\Entity\Invitation;
use App\User\Domain\Repository\InvitationReadRepositoryInterface;

/**
 * @extends BaseDbReadRepository<Invitation>
 */
class InvitationReadRepository extends BaseDbReadRepository implements InvitationReadRepositoryInterface
{
    public function getEntityClass(): string
    {
        return Invitation::class;
    }

    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry);
    }

    public function search(string $query, array $exclude_ids = [], int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('i');

        return $qb
            ->join('i.Group', 'g')
            ->where('g.name LIKE :query')
            ->andWhere($qb->expr()->notIn('g.id', $exclude_ids))
            ->setParameter('query', "%{$query}%")
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

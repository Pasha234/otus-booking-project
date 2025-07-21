<?php

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\Shared\Infrastructure\Repository\BaseRepository;


/**
 * @extends BaseRepository<User>
 */
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function getEntityClass(): string
    {
        return User::class;
    }

    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry);
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

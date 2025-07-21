<?php

namespace App\User\Infrastructure\Repository;

use Doctrine\Persistence\ManagerRegistry;
use App\Shared\Infrastructure\Repository\BaseRepository;
use App\User\Domain\Entity\Invitation;
use App\User\Domain\Repository\InvitationRepositoryInterface;

/**
 * @extends BaseRepository<Invitation>
 */
class InvitationRepository extends BaseRepository implements InvitationRepositoryInterface
{
    public function getEntityClass(): string
    {
        return Invitation::class;
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

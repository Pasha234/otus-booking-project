<?php

namespace App\Booking\Infrastructure\Repository;

use App\Booking\Domain\Entity\Resource;
use App\Booking\Domain\Repository\ResourceRepositoryInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Shared\Infrastructure\Repository\BaseRepository;

/**
 * @extends BaseRepository<Resource>
 */
class ResourceRepository extends BaseRepository implements ResourceRepositoryInterface
{
    public function getEntityClass(): string
    {
        return Resource::class;
    }

    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry);
    }

//    /**
//     * @return Resource[] Returns an array of Resource objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Resource
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

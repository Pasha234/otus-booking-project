<?php

namespace App\Booking\Infrastructure\Repository;

use App\Booking\Domain\Entity\Group;
use Doctrine\Persistence\ManagerRegistry;
use App\Shared\Infrastructure\Repository\BaseRepository;
use App\Booking\Domain\Repository\GroupRepositoryInterface;

/**
 * @extends BaseRepository<Group>
 */
class GroupRepository extends BaseRepository implements GroupRepositoryInterface
{
    public function getEntityClass(): string
    {
        return Group::class;
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

    /**
     * @return Group[]
     */
    public function findByOwnerId(string $ownerId): array
    {
        return $this->findBy(['owner' => $ownerId], ['created_at' => 'DESC']);
    }

    /**
     * @return Group[]
     */
    public function findByParticipantId(string $participantId): array
    {
        return $this->createQueryBuilder('g')
            ->innerJoin('g.groupParticipants', 'gp')
            ->where('gp.user = :participantId')
            ->setParameter('participantId', $participantId)
            ->orderBy('g.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

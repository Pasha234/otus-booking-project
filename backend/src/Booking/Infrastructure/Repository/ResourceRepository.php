<?php

namespace App\Booking\Infrastructure\Repository;

use App\Booking\Domain\Entity\Resource;
use App\Booking\Domain\Repository\ResourceRepositoryInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Shared\Infrastructure\Repository\BaseRepository;
use Symfony\Component\Uid\Uuid;

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

    public function findBookedQuantities(array $resourceIds, \DateTimeImmutable $start_at, \DateTimeImmutable $end_at, ?string $excludeBookingId = null): array
    {
        if (empty($resourceIds)) {
            return [];
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('r.id as resourceId, SUM(br.quantity) as bookedQuantity')
            ->from('App\Booking\Domain\Entity\BookedResource', 'br')
            ->join('br.booking', 'b')
            ->join('br.resource', 'r')
            ->where($qb->expr()->in('r.id', ':resourceIds'))
            ->andWhere('b.end_at > :start_at')
            ->andWhere('b.start_at < :end_at')
            ->groupBy('r.id');

        if ($excludeBookingId) {
            $qb->andWhere('b.id != :excludeBookingId')
                ->setParameter('excludeBookingId', $excludeBookingId);
        }

        $qb->setParameter('resourceIds', array_values($resourceIds))
            ->setParameter('start_at', $start_at)
            ->setParameter('end_at', $end_at);

        $results = $qb->getQuery()->getArrayResult();

        // The result is an array of arrays, e.g., [['resourceId' => 'uuid', 'bookedQuantity' => '5']].
        // We need to format it as [resourceId => bookedQuantity].
        $results = array_map(function(array $result) {
            return [
                'resourceId' => (string) $result['resourceId'] ?? '',
                'bookedQuantity' => $result['bookedQuantity'] ?? null,
            ];
        }, $results);
        return array_column($results, 'bookedQuantity', 'resourceId');
    }
}

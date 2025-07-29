<?php

namespace App\Booking\Infrastructure\Repository;

use App\Booking\Domain\Entity\Booking;
use Doctrine\Persistence\ManagerRegistry;
use App\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Shared\Infrastructure\Repository\BaseRepository;

/**
 * @extends BaseRepository<Booking>
 */
class BookingRepository extends BaseRepository implements BookingRepositoryInterface
{
    public function getEntityClass(): string
    {
        return Booking::class;
    }

    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry);
    }

    /**
     * @return Booking[]
     */
    public function getListByFilters(string $group_id, \DateTimeImmutable|null $start_at = null, \DateTimeImmutable|null $end_at = null): array
    {
        $qb = $this->createQueryBuilder('b');
 
        $qb->andWhere('b.group = :group_id')
            ->setParameter('group_id', $group_id);
 
        if ($start_at) {
            $qb->andWhere('b.end_at > :start_at')
                ->setParameter('start_at', $start_at);
        }
 
        if ($end_at) {
            $qb->andWhere('b.start_at < :end_at')
                ->setParameter('end_at', $end_at);
        }
 
        return $qb->orderBy('b.start_at', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

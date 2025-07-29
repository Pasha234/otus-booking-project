<?php

namespace App\Booking\Domain\Repository;

use App\Booking\Domain\Entity\Booking;
use App\Shared\Domain\Repository\WriteRepositoryInterface;
use DateTimeImmutable;

/**
 * @extends WriteRepositoryInterface<Booking>
 */
interface BookingRepositoryInterface extends WriteRepositoryInterface
{
    /**
     * @return Booking[]
     */
    public function getListByFilters(string $group_id, ?DateTimeImmutable $start_at = null, ?DateTimeImmutable $end_at = null): array;
}
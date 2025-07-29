<?php

namespace App\Booking\Domain\Repository;

use App\Booking\Domain\Entity\Booking;
use App\Shared\Domain\Repository\ReadRepositoryInterface;
use DateTimeImmutable;

/**
 * @extends ReadRepositoryInterface<Booking>
 */
interface BookingReadRepositoryInterface extends ReadRepositoryInterface
{
    /**
     * @return Booking[]
     */
    public function getListByFilters(string $group_id, ?DateTimeImmutable $start_at = null, ?DateTimeImmutable $end_at = null): array;
}
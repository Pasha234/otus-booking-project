<?php

namespace App\Booking\Domain\Repository;

use App\Booking\Domain\Entity\Resource;
use App\Shared\Domain\Repository\WriteRepositoryInterface;

/**
 * @extends WriteRepositoryInterface<Resource>
 */
interface ResourceRepositoryInterface extends WriteRepositoryInterface
{
    /**
     * @param string[] $resourceIds
     * @return array<string, int> Returns an associative array of resourceId => bookedQuantity
     */
    public function findBookedQuantities(array $resourceIds, \DateTimeImmutable $startAt, \DateTimeImmutable $endAt, ?string $excludeBookingId = null): array;
}

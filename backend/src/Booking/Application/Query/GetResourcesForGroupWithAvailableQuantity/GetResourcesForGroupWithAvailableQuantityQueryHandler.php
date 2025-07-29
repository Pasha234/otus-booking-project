<?php

namespace App\Booking\Application\Query\GetResourcesForGroupWithAvailableQuantity;

use App\Booking\Domain\Entity\Resource;
use App\Shared\Application\Query\QueryHandlerInterface;
use App\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Booking\Domain\Repository\ResourceRepositoryInterface;
use App\Booking\Application\DTO\Basic\ResourceWithAvailableDTO;

class GetResourcesForGroupWithAvailableQuantityQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly ResourceRepositoryInterface $resourceRepository,
        private readonly BookingRepositoryInterface $bookingRepository
    ) {
    }

    public function __invoke(GetResourcesForGroupWithAvailableQuantityQuery $query): array
    {
        /** @var Resource[] $resources */
        $resources = $this->resourceRepository->findBy(['group' => $query->group_id]);

        if (empty($resources)) {
            return [];
        }

        $resourceIds = array_map(fn(Resource $resource) => $resource->getId(), $resources);

        $bookedByOthers = $this->resourceRepository->findBookedQuantities(
            $resourceIds,
            $query->start_at,
            $query->end_at,
            $query->current_booking_id
        );

        $bookedByCurrent = [];
        if ($query->current_booking_id) {
            $booking = $this->bookingRepository->getById($query->current_booking_id);
            if ($booking) {
                foreach ($booking->getBookedResources() as $bookedResource) {
                    $bookedByCurrent[$bookedResource->getResource()->getId()] = $bookedResource->getQuantity();
                }
            }
        }

        return array_map(function(Resource $resource) use ($bookedByOthers, $bookedByCurrent) {
            $resourceId = $resource->getId();
            $totalQuantity = $resource->getQuantity();
            $quantityBookedByOthers = (int)($bookedByOthers[$resourceId] ?? 0);
            $quantityBookedByCurrent = (int)($bookedByCurrent[$resourceId] ?? 0);

            return ResourceWithAvailableDTO::fromEntity(
                $resource,
                $totalQuantity - $quantityBookedByOthers,
                $quantityBookedByCurrent
            );
        }, $resources);
    }
}
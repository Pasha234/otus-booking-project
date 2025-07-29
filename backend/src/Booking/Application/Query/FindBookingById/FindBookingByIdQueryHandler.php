<?php

namespace App\Booking\Application\Query\FindBookingById;

use App\Booking\Application\DTO\GetBookingList\BookingDTO;
use App\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Shared\Application\Query\QueryHandlerInterface;

class FindBookingByIdQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepository
    )
    {
        
    }

    public function __invoke(FindBookingByIdQuery $query): ?BookingDTO
    {
        $booking = $this->bookingRepository->getById($query->id);

        if (!$booking) {
            return null;
        }

        return BookingDTO::fromEntity($booking);
    }
}
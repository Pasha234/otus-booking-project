<?php

namespace App\Booking\Application\Query\GetBookingList;

use App\Booking\Application\DTO\GetBookingList\BookingDTO;
use App\Booking\Domain\Entity\Booking;
use App\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Shared\Application\Exception\NotFoundInRepositoryException;
use App\Shared\Application\Query\QueryHandlerInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;

class GetBookingListQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepository,
        private UserRepositoryInterface $userRepository,
    )
    {
        
    }

    public function __invoke(GetBookingListQuery $query): array
    {
        $bookings = $this->bookingRepository->getListByFilters(
            $query->group_id,
            new DateTimeImmutable($query->start_at),
            new DateTimeImmutable($query->end_at),
        );

        $user = null;
        if ($query->user_id) {
            $user = $this->userRepository->getById($query->user_id);

            if (!$user) {
                throw new NotFoundInRepositoryException("User with id {$query->user_id} not found");
            }
        }

        return array_map(function(Booking $booking) use ($user) {
            return BookingDTO::fromEntity($booking, $user);
        }, $bookings);
    }
}
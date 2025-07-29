<?php

namespace App\Booking\Application\Command\DeleteBooking;

use App\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Shared\Application\Command\CommandHandlerInterface;

class DeleteBookingCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepository,
    )
    {
        
    }

    public function __invoke(DeleteBookingCommand $command): void
    {
        $this->bookingRepository->deleteById($command->id);
    }
}
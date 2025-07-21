<?php

namespace App\Booking\Domain\Repository;

use App\Booking\Domain\Entity\Booking;

interface BookingRepositoryInterface
{
    public function save(Booking $booking): void;

    public function findById(string $id): Booking;
}

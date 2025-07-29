<?php

namespace App\Booking\Application\DTO\GetBookingList;

use DateTimeImmutable;
use App\User\Domain\Entity\User;
use App\User\Application\DTO\UserDTO;
use App\Booking\Domain\Entity\Booking;
use App\Booking\Domain\Entity\BookedResource;
use App\Booking\Application\DTO\Basic\BookedResourceDTO;

class BookingDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?DateTimeImmutable $start_at,
        public readonly ?DateTimeImmutable $end_at,
        public readonly ?UserDTO $author,
        public readonly ?array $users,
        public readonly ?array $booked_resources,
        public readonly bool $is_author,
    )
    {
        
    }

    public static function fromEntity(Booking $booking, ?User $user = null): static
    {
        return new static(
            $booking->getId(),
            $booking->getTitle(),
            $booking->getDescription(),
            $booking->getStartAt(),
            $booking->getEndAt(),
            UserDTO::fromEntity($booking->getAuthor()),
            array_map(function(User $user) {
                return UserDTO::fromEntity($user);
            }, $booking->getUsers()->toArray()),
            array_map(function(BookedResource $bookedResource) {
                return BookedResourceDTO::fromEntity($bookedResource);
            }, $booking->getBookedResources()->toArray()),
            $booking->getAuthor()->getId() === $user?->getId(),
        );
    }
}
<?php

namespace App\Booking\Infrastructure\Redis;

use App\Booking\Domain\Entity\Booking;
use Redis;

class BookingRedisWriter
{
    private const BOOKING_KEY_PREFIX = 'booking:';
    private const GROUP_BOOKINGS_BY_DATE_PREFIX = 'group_bookings_by_date:';
    private const INDEX_PREFIX = 'index:';

    public function __construct(private readonly Redis $redis)
    {
    }

    public function save(Booking $booking): void
    {
        $this->redis->set(
            self::BOOKING_KEY_PREFIX . $booking->getId(),
            json_encode($this->transform($booking))
        );

        // Add to group index
        $this->redis->sAdd(
            self::INDEX_PREFIX . 'group_id:' . $booking->getGroup()->getId(),
            $booking->getId()
        );

        // Add to sorted set for date range queries.
        // ZADD will update the score if the member already exists.
        $this->redis->zAdd(
            self::GROUP_BOOKINGS_BY_DATE_PREFIX . $booking->getGroup()->getId(),
            $booking->getStartAt()->getTimestamp(),
            $booking->getId()
        );
    }

    public function delete(Booking $booking): void
    {
        $this->redis->del(self::BOOKING_KEY_PREFIX . $booking->getId());
        $this->removeFromIndices($booking->getId(), $booking->getGroup()->getId());
    }

    public function removeFromIndices(string $bookingId, string $groupId): void
    {
        $this->redis->sRem(
            self::INDEX_PREFIX . 'group_id:' . $groupId,
            $bookingId
        );

        $this->redis->zRem(
            self::GROUP_BOOKINGS_BY_DATE_PREFIX . $groupId,
            $bookingId
        );
    }

    private function transform(Booking $booking): array
    {
        $author = $booking->getAuthor();
        $participants = [];
        foreach ($booking->getUsers() as $user) {
            $participants[] = [
                'id' => $user->getId(),
                'full_name' => $user->getFullName(),
                'email' => $user->getEmail(),
            ];
        }

        $bookedResources = [];
        foreach ($booking->getBookedResources() as $bookedResource) {
            $resource = $bookedResource->getResource();
            $bookedResources[] = [
                'id' => $bookedResource->getId(),
                'quantity' => $bookedResource->getQuantity(),
                'resource' => [
                    'id' => $resource->getId(),
                    'name' => $resource->getName(),
                    'quantity' => $resource->getQuantity(),
                    'is_active' => $resource->isActive(),
                ],
            ];
        }

        return [
            'id' => $booking->getId(),
            'group_id' => $booking->getGroup()->getId(),
            'title' => $booking->getTitle(),
            'description' => $booking->getDescription(),
            'start_at' => $booking->getStartAt()->format(\DateTimeInterface::ATOM),
            'end_at' => $booking->getEndAt()->format(\DateTimeInterface::ATOM),
            'author' => [
                'id' => $author->getId(),
                'full_name' => $author->getFullName(),
                'email' => $author->getEmail(),
            ],
            'users' => $participants,
            'booked_resources' => $bookedResources,
        ];
    }
}

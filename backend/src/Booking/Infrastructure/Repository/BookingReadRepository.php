<?php

namespace App\Booking\Infrastructure\Repository;

use App\Booking\Domain\Entity\BookedResource;
use App\Booking\Domain\Entity\Booking;
use App\Booking\Domain\Entity\Group;
use App\Booking\Domain\Entity\Resource;
use App\Booking\Domain\Repository\BookingReadRepositoryInterface;
use App\User\Domain\Entity\User;
use DateTimeImmutable;
use InvalidArgumentException;
use Redis;
use ReflectionClass;
use Symfony\Component\Uid\Uuid;

class BookingReadRepository implements BookingReadRepositoryInterface
{
    private const BOOKING_KEY_PREFIX = 'booking:';
    private const GROUP_BOOKINGS_BY_DATE_PREFIX = 'group_bookings_by_date:';
    private const INDEX_PREFIX = 'index:';

    public function __construct(private Redis $redis)
    {
    }

    public function getById(string $id): ?object
    {
        $key = self::BOOKING_KEY_PREFIX . $id;
        $data = $this->redis->get($key);

        if ($data === false) {
            return null;
        }

        return $this->hydrate(json_decode($data, true));
    }

    /**
     * @return Booking[]
     */
    public function getListByFilters(string $group_id, ?DateTimeImmutable $start_at = null, ?DateTimeImmutable $end_at = null): array
    {
        $indexKey = self::GROUP_BOOKINGS_BY_DATE_PREFIX . $group_id;

        $start = $start_at ? $start_at->getTimestamp() : '-inf';
        $end = $end_at ? $end_at->getTimestamp() : '+inf';

        $bookingIds = $this->redis->zRangeByScore($indexKey, (string) $start, (string) $end);

        if (empty($bookingIds)) {
            return [];
        }

        $bookingIds = array_map(function(string $uid) {
            return Uuid::fromString($uid);
        }, $bookingIds);

        return $this->fetchAndHydrateByIds($bookingIds);
    }

    public function getList(?array $criteria = null): array
    {
        if (empty($criteria)) {
            throw new InvalidArgumentException('getList without criteria is not supported for this Redis repository.');
        }

        if (count($criteria) > 1) {
            // For multiple criteria, you would use SINTER on multiple index sets.
            throw new InvalidArgumentException('getList with multiple criteria is not supported by this simple Redis implementation.');
        }

        $field = key($criteria);
        $value = current($criteria);
        $indexKey = self::INDEX_PREFIX . "{$field}:{$value}"; // e.g., index:group_id:some-uuid

        $bookingIds = $this->redis->sMembers($indexKey);

        if (empty($bookingIds)) {
            return [];
        }

        return $this->fetchAndHydrateByIds($bookingIds);
    }

    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        // WARNING: This implementation fetches all matching items and then sorts/slices in PHP.
        // This can be inefficient for large datasets. A better implementation would use Redis's SORT
        // command (which requires storing data in Hashes) or a module like RediSearch.
        $allBookings = $this->getList($criteria);

        if ($orderBy) {
            usort($allBookings, function (Booking $a, Booking $b) use ($orderBy) {
                foreach ($orderBy as $field => $direction) {
                    $getter = 'get' . str_replace('_', '', ucwords($field, '_'));
                    if (method_exists($a, $getter) && method_exists($b, $getter)) {
                        $comparison = $a->{$getter}() <=> $b->{$getter}();
                        if ($comparison !== 0) {
                            return strtolower($direction) === 'asc' ? $comparison : -$comparison;
                        }
                    }
                }
                return 0;
            });
        }

        if ($limit !== null) {
            return array_slice($allBookings, $offset ?? 0, $limit);
        }

        return $allBookings;
    }

    public function findOneBy(array $criteria, ?array $orderBy = null): ?object
    {
        $results = $this->findBy($criteria, $orderBy, 1);
        return $results[0] ?? null;
    }

    public function search(string $query, array $exclude_ids = [], int $limit = 10): array
    {
        // WARNING: Full-text search is not efficiently implemented in basic Redis.
        // This implementation is for demonstration only and will be very slow on large datasets.
        // For production use, consider a dedicated search engine like Elasticsearch or the RediSearch module.
        $results = [];
        $iterator = null;
        $query = strtolower($query);

        // This scans keys matching the pattern, which is more efficient than KEYS * but can still be slow.
        while ($keys = $this->redis->scan($iterator, self::BOOKING_KEY_PREFIX . '*')) {
            foreach ($keys as $key) {
                $id = str_replace(self::BOOKING_KEY_PREFIX, '', $key);
                if (in_array($id, $exclude_ids)) {
                    continue;
                }

                $dataJson = $this->redis->get($key);
                if ($dataJson) {
                    $data = json_decode($dataJson, true);
                    if (str_contains(strtolower($data['title']), $query) || str_contains(strtolower($data['description']), $query)) {
                        $results[] = $this->hydrate($data);
                        if (count($results) >= $limit) {
                            return $results;
                        }
                    }
                }
            }
            if ($iterator === 0) { // scan finished
                break;
            }
        }

        return $results;
    }

    private function fetchAndHydrateByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $keys = array_map(fn($id) => self::BOOKING_KEY_PREFIX . $id, $ids);
        $bookingsData = $this->redis->mget($keys);

        $bookings = [];
        foreach ($bookingsData as $data) {
            if ($data !== false) {
                $bookings[] = $this->hydrate(json_decode($data, true));
            }
        }
        return $bookings;
    }

    private function hydrate(array $data): Booking
    {
        $booking = new Booking();
        $reflection = new ReflectionClass($booking);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($booking, $data['id']);

        $booking->setTitle($data['title'])->setDescription($data['description']);
        $booking->setStartAt(new DateTimeImmutable($data['start_at']))->setEndAt(new DateTimeImmutable($data['end_at']));

        $authorData = $data['author'];
        $author = new User();
        $authorRefl = new ReflectionClass($author);
        $authorRefl->getProperty('id')->setValue($author, $authorData['id']);
        $author->setFullName($authorData['full_name'])->setEmail($authorData['email']);
        $booking->setAuthor($author);

        $group = new Group();
        (new ReflectionClass($group))->getProperty('id')->setValue($group, $data['group_id']);
        $booking->setGroup($group);

        foreach ($data['users'] as $userData) {
            $participant = new User();
            $userRefl = new ReflectionClass($participant);
            $userRefl->getProperty('id')->setValue($participant, $userData['id']);
            $participant->setFullName($userData['full_name'])->setEmail($userData['email']);
            $booking->addUser($participant);
        }

        foreach ($data['booked_resources'] as $brData) {
            $resourceData = $brData['resource'];
            $resource = new Resource();
            $resourceRefl = new ReflectionClass($resource);
            $resourceRefl->getProperty('id')->setValue($resource, $resourceData['id']);
            $resource->setName($resourceData['name'])
                ->setQuantity($resourceData['quantity'])
                ->setIsActive($resourceData['is_active']);

            $bookedResource = new BookedResource();
            $brRefl = new ReflectionClass($bookedResource);
            $brRefl->getProperty('id')->setValue($bookedResource, $brData['id']);
            $bookedResource->setResource($resource)->setQuantity((int) $brData['quantity']);
            $booking->addBookedResource($bookedResource);
        }

        return $booking;
    }
}

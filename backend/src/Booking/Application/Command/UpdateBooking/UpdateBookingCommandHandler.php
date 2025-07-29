<?php

namespace App\Booking\Application\Command\UpdateBooking;

use App\Booking\Domain\Entity\BookedResource;
use App\Booking\Domain\Entity\Resource;
use App\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Booking\Domain\Repository\ResourceRepositoryInterface;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\Shared\Application\Exception\NotFoundInRepositoryException;
use App\User\Domain\Repository\UserRepositoryInterface;

class UpdateBookingCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookingRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly ResourceRepositoryInterface $resourceRepository,
    )
    {
        
    }

    public function __invoke(UpdateBookingCommand $command): void
    {
        $booking = $this->bookingRepository->getById($command->id);

        if (!$booking) {
            throw new NotFoundInRepositoryException('Booking not found');
        }

        if (null !== $command->title) {
            $booking->setTitle($command->title);
        }

        if (null !== $command->description) {
            $booking->setDescription($command->description);
        }

        $startAt = $command->startAt ?? $booking->getStartAt();
        $endAt = $command->endAt ?? $booking->getEndAt();

        if (null !== $command->startAt) {
            $booking->setStartAt($command->startAt);
        }

        if (null !== $command->endAt) {
            $booking->setEndAt($command->endAt);
        }

        if ($startAt >= $endAt) {
            throw new \DomainException('Start date must be before end date.');
        }

        if (null !== $command->userIds) {
            $booking->getUsers()->clear();
            foreach ($command->userIds as $userId) {
                $user = $this->userRepository->getById($userId);
                if (!$user) {
                    throw new NotFoundInRepositoryException("User with id {$userId} not found");
                }

                if (!$booking->getGroup()->checkUserIsInGroup($user)) {
                    throw new \DomainException("User {$user->getFullName()} is not a member of the group.");
                }
                $booking->addUser($user);
            }
        }

        if (null !== $command->resources) {
            foreach ($booking->getBookedResources() as $bookedResource) {
                $booking->removeBookedResource($bookedResource);
            }

            if (!empty($command->resources)) {
                $resourceIds = array_map(fn ($r) => $r['id'], $command->resources);
                $requestedQuantities = array_column($command->resources, 'quantity', 'id');

                /** @var Resource[] $resources */
                $resources = $this->resourceRepository->findBy(['id' => $resourceIds]);
                $resourcesById = [];
                foreach ($resources as $resource) {
                    if ($resource->getGroup() !== $booking->getGroup()) {
                        throw new NotFoundInRepositoryException("Resource {$resource->getId()} does not belong to this group.");
                    }
                    $resourcesById[$resource->getId()] = $resource;
                }

                $bookedQuantities = $this->resourceRepository->findBookedQuantities(
                    $resourceIds,
                    $startAt,
                    $endAt,
                    $booking->getId()->toRfc4122()
                );

                foreach ($resourceIds as $resourceId) {
                    $resource = $resourcesById[$resourceId] ?? null;
                    if (!$resource) {
                        throw new NotFoundInRepositoryException("Resource {$resourceId} does not exist");
                    }

                    $requestedQuantity = (int)($requestedQuantities[$resourceId] ?? 0);
                    if ($requestedQuantity === 0) {
                        continue;
                    }

                    $totalQuantity = $resource->getQuantity();
                    $currentlyBooked = (int)($bookedQuantities[$resourceId] ?? 0);
                    $availableQuantity = $totalQuantity - $currentlyBooked;

                    if ($availableQuantity < $requestedQuantity) {
                        throw new \DomainException("Not enough quantity for resource: \"{$resource->getName()}\". Requested: {$requestedQuantity}, Available: {$availableQuantity}");
                    }

                    $bookedResource = new BookedResource();
                    $bookedResource->setResource($resource)->setQuantity($requestedQuantity);
                    $booking->addBookedResource($bookedResource);
                }
            }
        }

        $this->bookingRepository->save($booking);
    }
}
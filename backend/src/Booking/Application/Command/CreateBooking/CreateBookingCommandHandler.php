<?php

namespace App\Booking\Application\Command\CreateBooking;

use App\Booking\Domain\Entity\BookedResource;
use App\Booking\Domain\Entity\Booking;
use App\Booking\Domain\Entity\Resource;
use App\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Booking\Domain\Repository\GroupParticipantRepositoryInterface;
use App\Booking\Domain\Repository\GroupRepositoryInterface;
use App\Booking\Domain\Repository\ResourceRepositoryInterface;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\Shared\Application\Exception\NotFoundInRepositoryException;
use App\User\Domain\Repository\UserRepositoryInterface;
use DomainException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

class CreateBookingCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly GroupParticipantRepositoryInterface $groupParticipantRepository,
        private readonly BookingRepositoryInterface $bookingRepository,
        private readonly ResourceRepositoryInterface $resourceRepository
    ) {
    }

    public function __invoke(CreateBookingCommand $command): void
    {
        $group = $this->groupRepository->getById($command->groupId);
        if (!$group) {
            throw new NotFoundInRepositoryException('Group not found');
        }

        $author = $this->userRepository->getById($command->authorId);
        if (!$author) {
            throw new NotFoundInRepositoryException('Author not found');
        }

        if (!$group->checkUserIsInGroup($author)) {
            throw new AccessDeniedException('User is not a member of this group.');
        }

        $booking = new Booking();
        $booking->setTitle($command->title)
            ->setDescription($command->description)
            ->setStartAt($command->startAt)
            ->setEndAt($command->endAt)
            ->setAuthor($author)
            ->setGroup($group);

        foreach ($command->userIds as $userId) {
            $participant = $this->groupParticipantRepository->findOneBy([
                'user' => $userId,
                'group' => $group->getId(),
            ]);
            if (!$participant) {
                throw new NotFoundInRepositoryException("User with id {$userId} not found in group");
            }
            $booking->addUser($participant->getUser());
        }

        if (!empty($command->resources)) {
            $resourceIds = array_map(fn($r) => $r['id'] ?? '', $command->resources);
            foreach($resourceIds as $resourceId) {
                if (!Uuid::isValid($resourceId)) {
                    throw new NotFoundInRepositoryException("Resource with id {$resourceId} not found");
                }
            }
            $requestedQuantities = array_column($command->resources, 'quantity', 'id');

            /** @var Resource[] $resources */
            $resources = $this->resourceRepository->findBy(['id' => $resourceIds]);
            $resourcesById = [];
            foreach ($resources as $resource) {
                if ($resource->getGroup() !== $group) {
                    throw new NotFoundInRepositoryException("Resource with id {$resource->getId()} does not exist");
                }
                $resourcesById[$resource->getId()] = $resource;
            }

            $bookedQuantities = $this->resourceRepository->findBookedQuantities($resourceIds, $command->startAt, $command->endAt);

            foreach ($resourceIds as $resourceId) {
                $resource = $resourcesById[$resourceId] ?? null;
                if (!$resource) {
                    throw new NotFoundInRepositoryException("Resource with id {$resourceId} does not exist");
                }

                $totalQuantity = $resource->getQuantity();
                $currentlyBooked = (int)($bookedQuantities[$resourceId] ?? 0);
                $availableQuantity = $totalQuantity - $currentlyBooked;
                $requestedQuantity = $requestedQuantities[$resourceId];

                if ($availableQuantity < $requestedQuantity) {
                    throw new \DomainException("Not enough quantity for resource: \"{$resource->getName()}\". Requested: {$requestedQuantity}, Available: {$availableQuantity}");
                }

                $bookedResource = new BookedResource();
                $bookedResource->setResource($resource)->setQuantity($requestedQuantity);
                $booking->addBookedResource($bookedResource);
            }
        }

        $this->bookingRepository->save($booking);
    }
}
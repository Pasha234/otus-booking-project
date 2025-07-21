<?php

namespace App\Booking\Application\Command\CreateBookingGroup;

use App\Booking\Domain\Entity\Group;
use App\Booking\Domain\Repository\GroupRepositoryInterface;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\Shared\Application\Exception\NotFoundInRepositoryException;
use App\User\Domain\Repository\UserRepositoryInterface;

class CreateBookingGroupCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    public function __invoke(CreateBookingGroupCommand $command): string
    {
        $owner = $this->userRepository->findOneBy([
            'email' => $command->ownerEmail
        ]);

        if (!$owner) {
            throw new NotFoundInRepositoryException('Owner not found');
        }

        $bookingGroup = new Group();
        $bookingGroup->setName($command->name);
        $bookingGroup->setDescription($command->description);
        $bookingGroup->setOwner($owner);

        $this->groupRepository->save($bookingGroup);

        return $bookingGroup->getId();
    }
}
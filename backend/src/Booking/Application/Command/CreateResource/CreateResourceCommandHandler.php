<?php

namespace App\Booking\Application\Command\CreateResource;

use App\Booking\Domain\Entity\Resource;
use App\Booking\Domain\Repository\GroupRepositoryInterface;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\Booking\Application\Exception\ResourceExistsException;
use App\Booking\Domain\Repository\ResourceRepositoryInterface;
use App\Shared\Application\Exception\NotFoundInRepositoryException;

class CreateResourceCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private ResourceRepositoryInterface $resourceRepository,
        private GroupRepositoryInterface $groupRepository,
    )
    {
        
    }

    /**
     * @return string ID ресурса
     */
    public function __invoke(CreateResourceCommand $command): string
    {
        $group = $this->groupRepository->getById($command->group_id);

        if (!$group) {
            throw new NotFoundInRepositoryException('Group not found');
        }

        $existingResource = $this->resourceRepository->findOneBy([
            'group' => $command->group_id,
            'name' => $command->name,
        ]);

        if ($existingResource) {
            throw new ResourceExistsException("This resource already exists");
        }

        $resource = new Resource();
        $resource->setGroup($group);
        $resource->setName($command->name);
        $resource->setIsActive(true);
        $resource->setQuantity($command->quantity);

        $this->resourceRepository->save($resource);

        return $resource->getId();
    }
}
<?php

namespace App\Booking\Domain\Repository;

use App\Booking\Domain\Entity\Group;
use App\Shared\Domain\Repository\WriteRepositoryInterface;

/**
 * @extends WriteRepositoryInterface<Group>
 */
interface GroupRepositoryInterface extends WriteRepositoryInterface
{
    /**
     * @return Group[]
     */
    public function findByOwnerId(string $ownerId): array;

    /**
     * @return Group[]
     */
    public function findByParticipantId(string $participantId): array;
    
}
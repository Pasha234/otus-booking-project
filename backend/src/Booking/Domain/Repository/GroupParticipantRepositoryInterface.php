<?php

namespace App\Booking\Domain\Repository;

use App\Booking\Domain\Entity\GroupParticipant;
use App\Shared\Domain\Repository\WriteRepositoryInterface;

/**
 * @extends WriteRepositoryInterface<GroupParticipant>
 */
interface GroupParticipantRepositoryInterface extends WriteRepositoryInterface
{
}
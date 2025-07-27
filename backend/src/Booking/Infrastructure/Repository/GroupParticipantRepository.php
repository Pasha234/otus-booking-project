<?php

namespace App\Booking\Infrastructure\Repository;

use Doctrine\Persistence\ManagerRegistry;
use App\Booking\Domain\Entity\GroupParticipant;
use App\Shared\Infrastructure\Repository\BaseRepository;
use App\Booking\Domain\Repository\GroupParticipantRepositoryInterface;

/**
 * @extends BaseRepository<GroupParticipant>
 */
class GroupParticipantRepository extends BaseRepository implements GroupParticipantRepositoryInterface
{
    public function getEntityClass(): string
    {
        return GroupParticipant::class;
    }

    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry);
    }
}

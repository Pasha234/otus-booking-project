<?php

namespace App\Booking\Domain\Repository;

use App\Booking\Domain\Entity\GroupParticipant;

interface GroupParticipantRepositoryInterface
{
    public function save(GroupParticipant $group_participant): void;

    public function findById(string $id): GroupParticipant;
}

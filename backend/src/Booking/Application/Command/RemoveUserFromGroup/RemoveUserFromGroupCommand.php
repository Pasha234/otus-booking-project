<?php

namespace App\Booking\Application\Command\RemoveUserFromGroup;

use App\Shared\Application\Command\CommandInterface;

class RemoveUserFromGroupCommand implements CommandInterface
{
    public function __construct(
        public readonly string $userId,
        public readonly string $groupId,
        public readonly string $adminId,
    ) {
    }
}
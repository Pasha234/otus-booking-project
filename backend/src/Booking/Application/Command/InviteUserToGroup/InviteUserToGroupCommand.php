<?php

namespace App\Booking\Application\Command\InviteUserToGroup;

use App\Shared\Application\Command\CommandInterface;

class InviteUserToGroupCommand implements CommandInterface
{
    public function __construct(
        public readonly string $groupId,
        public readonly string $invitedUserEmail,
        public readonly string $inviterId
    ) {
    }
}
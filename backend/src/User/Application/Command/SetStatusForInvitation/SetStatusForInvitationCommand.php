<?php

namespace App\User\Application\Command\SetStatusForInvitation;

use App\Shared\Application\Command\CommandInterface;

class SetStatusForInvitationCommand implements CommandInterface
{
    public function __construct(
        public readonly string $invitation_id,
        public readonly string $user_id,
        public readonly bool $is_accepted
    )
    {
        
    }
}
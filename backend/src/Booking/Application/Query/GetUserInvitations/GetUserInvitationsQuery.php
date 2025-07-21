<?php

namespace App\Booking\Application\Query\GetUserInvitations;

use App\Shared\Application\Query\QueryInterface;

class GetUserInvitationsQuery implements QueryInterface
{
    public function __construct(
        public readonly string $user_email
    ) {
    }
}
<?php

namespace App\Booking\Application\Query\SearchUsersForInvitations;

use App\Shared\Application\Query\QueryInterface;

class SearchUsersForInvitationsQuery implements QueryInterface
{
    public function __construct(
        public readonly string $group_id,
        public readonly string $query,
        public readonly int $limit,
    )
    {
        
    }
}
<?php

namespace App\User\Application\Query\SearchUsers;

use App\Shared\Application\Query\QueryInterface;

class SearchUsersQuery implements QueryInterface
{
    public function __construct(
        public readonly string $query
    )
    {
        
    }
}
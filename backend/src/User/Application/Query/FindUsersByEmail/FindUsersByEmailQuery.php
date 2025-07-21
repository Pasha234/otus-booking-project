<?php

namespace App\User\Application\Query\FindUsersByEmail;

use App\Shared\Application\Query\QueryInterface;

class FindUsersByEmailQuery implements QueryInterface
{
    public function __construct(
        public readonly string $email
    )
    {
        
    }
}
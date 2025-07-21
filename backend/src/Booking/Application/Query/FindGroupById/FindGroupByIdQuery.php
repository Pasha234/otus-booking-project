<?php

namespace App\Booking\Application\Query\FindGroupById;

use App\Shared\Application\Query\QueryInterface;

class FindGroupByIdQuery implements QueryInterface
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $userId = null
    ) {
    }
}
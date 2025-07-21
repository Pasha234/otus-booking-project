<?php

namespace App\Booking\Application\Command\CreateResource;

use App\Shared\Application\Command\CommandInterface;

class CreateResourceCommand implements CommandInterface
{
    public function __construct(
        public readonly string $group_id,
        public readonly string $name,
        public readonly int $quantity,
    )
    {
        
    }
}
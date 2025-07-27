<?php

namespace App\Booking\Infrastructure\Request;

use Symfony\Component\Validator\Constraints as Assert;

class RemoveUserFromGroupRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public ?string $user_id = null,
    )
    {

    }
}
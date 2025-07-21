<?php

namespace App\Booking\Infrastructure\Request;

use Symfony\Component\Validator\Constraints as Assert;

class InviteUserToGroupRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email()]
        public string $email
    )
    {

    }
}
<?php

namespace App\Booking\Infrastructure\Request;

use Symfony\Component\Validator\Constraints as Assert;

class GetBookingListRequest
{
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    #[Assert\Date(message: 'The start date is not a valid date.')]
    public ?string $start_at = null;

    #[Assert\NotBlank]
    #[Assert\Type('string')]
    #[Assert\Date(message: 'The end date is not a valid date.')]
    public ?string $end_at = null;
}
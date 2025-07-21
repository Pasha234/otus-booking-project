<?php

namespace App\User\Infrastructure\Request;

use Symfony\Component\Validator\Constraints as Assert;

class RegistrationRequest
{
    public function __construct(
        #[Assert\NotBlank()]
        public readonly string $full_name = '',
        #[Assert\NotBlank()]
        #[Assert\Email()]
        public readonly string $email = '',
        #[Assert\NotBlank()]
        public readonly string $password = '',
        #[Assert\NotBlank()]
        #[Assert\EqualTo(
        propertyPath: 'password',
        message: 'The passwords do not match.'
        )]
        public readonly string $password_confirmation = '',
    ) {}
}
<?php

namespace App\User\Application\Command\CreateUser;

use App\Shared\Application\Command\CommandInterface;

class CreateUserCommand implements CommandInterface
{
    public function __construct(
        public readonly string $email,
        public readonly string $full_name,
        public readonly string $password,
    ) {}
}
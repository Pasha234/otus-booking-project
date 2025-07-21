<?php

namespace App\User\Domain\Repository;

use App\Shared\Domain\Repository\ReadRepositoryInterface;
use App\User\Domain\Entity\User;

/**
 * @extends ReadRepositoryInterface<User>
 */
interface UserReadRepositoryInterface extends ReadRepositoryInterface
{
}
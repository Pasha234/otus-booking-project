<?php

namespace App\User\Domain\Repository;

use App\Shared\Domain\Repository\WriteRepositoryInterface;
use App\User\Domain\Entity\User;

/**
 * @extends WriteRepositoryInterface<User>
 */
interface UserRepositoryInterface extends WriteRepositoryInterface
{
}
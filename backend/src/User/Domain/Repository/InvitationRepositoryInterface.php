<?php

namespace App\User\Domain\Repository;

use App\Shared\Domain\Repository\WriteRepositoryInterface;
use App\User\Domain\Entity\Invitation;

/**
 * @extends WriteRepositoryInterface<Invitation>
 */
interface InvitationRepositoryInterface extends WriteRepositoryInterface
{
}

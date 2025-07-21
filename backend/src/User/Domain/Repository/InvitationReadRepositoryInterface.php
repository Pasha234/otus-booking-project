<?php

namespace App\User\Domain\Repository;

use App\Shared\Domain\Repository\ReadRepositoryInterface;
use App\User\Domain\Entity\Invitation;

/**
 * @extends ReadRepositoryInterface<Invitation>
 */
interface InvitationReadRepositoryInterface extends ReadRepositoryInterface
{
}

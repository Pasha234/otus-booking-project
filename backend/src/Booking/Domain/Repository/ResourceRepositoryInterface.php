<?php

namespace App\Booking\Domain\Repository;

use App\Booking\Domain\Entity\Resource;
use App\Shared\Domain\Repository\WriteRepositoryInterface;

/**
 * @extends WriteRepositoryInterface<Resource>
 */
interface ResourceRepositoryInterface extends WriteRepositoryInterface
{
}

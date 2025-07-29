<?php

namespace App\Booking\Infrastructure\Redis;

use App\Booking\Domain\Entity\Booking;
use App\Booking\Domain\Entity\Group;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postPersist, priority: 490, connection: 'default')]
#[AsDoctrineListener(event: Events::postUpdate, priority: 490, connection: 'default')]
#[AsDoctrineListener(event: Events::preRemove, priority: 490, connection: 'default')]
class BookingRedisSyncSubscriber
{
    public function __construct(private readonly BookingRedisWriter $writer)
    {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof Booking) {
            $this->writer->save($entity);
        }
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Booking) {
            return;
        }

        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($entity);

        // If the group has changed, we need to remove the booking from the old group's indices in Redis.
        if (isset($changeSet['group'])) {
            /** @var Group $oldGroup */
            $oldGroup = $changeSet['group'][0];
            if ($oldGroup->getId() !== $entity->getGroup()->getId()) {
                $this->writer->removeFromIndices($entity->getId(), $oldGroup->getId());
            }
        }

        $this->writer->save($entity);
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof Booking) {
            $this->writer->delete($entity);
        }
    }
}

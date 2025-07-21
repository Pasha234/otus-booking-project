<?php
declare(strict_types=1);

namespace App\Shared\Infrastructure\Repository;

use EntityInterface;
use InvalidArgumentException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\Uid\Uuid;

/**
 * @template T
 * @extends ServiceEntityRepository<T>
 **/
abstract class BaseDbReadRepository extends ServiceEntityRepository
{
    /**
     * @return class-string<T>
     */
    abstract public function getEntityClass(): string;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, $this->getEntityClass());
    }

    /**
     * @return T[]
     */
    public function getList(?array $criteria = null): array
    {
        if (!$criteria) {
            return $this->findAll();
        }

        return $this->findBy($criteria);
    }

    /**
     * @param string $id
     * @return T|null
     */
    public function getById(string $id): ?object
    {
        if (!Uuid::isValid($id)) {
            return null;
        }
        return $this->find($id);
    }
}
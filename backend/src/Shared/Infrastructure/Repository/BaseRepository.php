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
 * @template T of EntityInterface
 * @extends ServiceEntityRepository<T>
 **/
abstract class BaseRepository extends ServiceEntityRepository
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
     * @param T $entity
     * @return T
     */
    public function save(object $entity): object
    {
        if (!$entity->getId()) {
            $this->getEntityManager()->persist($entity);
        }

        $this->getEntityManager()->flush();

        return $entity;
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

    /**
     * @param int $id
     * @return void
     */
    public function deleteById(int $id): void
    {
        $category = $this->find($id);

        if (!$category) {
            throw new NotFoundHttpException(sprintf("The {$this->getEntityClass()} with ID '%s' doesn't exist", $id));
        }

        $this->delete($category);
    }

    /**
     * @param T $entity
     * @return void
     */
    public function delete(object $entity): void
    {
        if ($entity::class !== $this->getEntityClass()) {
            throw new InvalidArgumentException(
                sprintf('You can only pass %s entity to this repository.', $this->getEntityClass())
            );
        }

        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
    }
}
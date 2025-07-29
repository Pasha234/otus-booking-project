<?php

namespace App\Shared\Domain\Repository;

use EntityInterface;

/**
 * @template T
 **/
interface WriteRepositoryInterface
{
    /**
     * @param T $entity
     * @return T
     */
    public function save(object $entity): object;

    /**
     * @return T[]
     */
    public function getList(?array $criteria = null): array;

    /**
     * @param string $id
     * @return T|null
     */
    public function getById(string $id): ?object;

    /**
     * @param string $id
     * @return void
     */
    public function deleteById(string $id): void;

    /**
     * @param T $entity
     * @return void
     */
    public function delete(object $entity): void;

    /**
     * @return T[]
     */
    public function findBy(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array;

    /**
     * @return ?T
     */
    public function findOneBy(
        array $criteria,
        array|null $orderBy = null
    ): object|null;
}
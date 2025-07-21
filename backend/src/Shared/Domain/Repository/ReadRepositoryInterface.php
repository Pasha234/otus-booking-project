<?php

namespace App\Shared\Domain\Repository;

use EntityInterface;

/**
 * @template T
 **/
interface ReadRepositoryInterface
{
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

    /**
     * @return T[]
     */
    public function search(
        string $query,
        array $exclude_ids = [],
        int $limit = 10,
    ): array;
}
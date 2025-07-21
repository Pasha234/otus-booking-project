<?php

namespace App\Shared\Infrastructure\Bus;

use Symfony\Component\Messenger\HandleTrait;
use App\Shared\Application\Query\QueryInterface;
use App\Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class QueryBus implements QueryBusInterface
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $queryBus,
    ) {
        $this->messageBus = $queryBus;
    }

    public function execute(QueryInterface $query): mixed
    {
        return $this->handle($query);
    }
}
<?php

namespace App\Shared\Infrastructure\Bus;

use Symfony\Component\Messenger\HandleTrait;
use App\Shared\Application\Command\CommandInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Shared\Application\Command\CommandBusInterface;

class CommandBus implements CommandBusInterface
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $commandBus,
    ) {
        $this->messageBus = $commandBus;
    }

    public function execute(CommandInterface $command): mixed
    {
        return $this->handle($command);
    }
}
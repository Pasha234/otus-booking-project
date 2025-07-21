<?php

namespace App\Tests\Unit\User;

use Faker\Factory;
use Faker\Generator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\User\Infrastructure\Repository\UserRepository;
use App\Shared\Application\Command\CommandBusInterface;
use App\User\Application\Command\CreateUser\CreateUserCommand;

class CreateUserTest extends WebTestCase
{
    private Generator $faker;
    private CommandBusInterface $commandBus;
    private UserRepository $userRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create();
        $this->commandBus = $this->getContainer()->get(CommandBusInterface::class);
        $this->userRepository = $this->getContainer()->get(UserRepository::class);
    }

    public function test_user_created_successfully(): void
    {
        $command = new CreateUserCommand(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
        );

        $userId = $this->commandBus->execute($command);

        $user = $this->userRepository->findOneBy([
            'id' => $userId
        ]);
        $this->assertNotEmpty($user);
    }
}

<?php

namespace Tests\Unit\User;

use Faker\Factory;
use Faker\Generator;
use App\User\Domain\Entity\User;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use App\Shared\Application\Query\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use App\User\Infrastructure\Repository\UserRepository;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use App\User\Application\Query\FindUsersByEmail\FindUsersByEmailQuery;

class FindUsersByEmailTest extends WebTestCase
{
    private Generator $faker;
    private QueryBusInterface $queryBus;
    private UserRepository $userRepository;
    private ORMExecutor $executor;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create();
        $this->queryBus = $this->getContainer()->get(QueryBusInterface::class);
        $this->userRepository = $this->getContainer()->get(UserRepository::class);

        $entityManager = $this->getContainer()->get(EntityManagerInterface::class);
        $loader = new Loader();
        $loader->addFixture(new UserFixtures());
        $this->executor = new ORMExecutor($entityManager, new ORMPurger());
        $this->executor->execute($loader->getFixtures());
    }

    public function test_find_user_by_email()
    {
        $query = new FindUsersByEmailQuery(
            $this->executor->getReferenceRepository()->getReference('test-user', User::class)->getEmail(),
        );

        $users = $this->queryBus->execute($query);

        $this->assertNotEmpty($users);
    }
}
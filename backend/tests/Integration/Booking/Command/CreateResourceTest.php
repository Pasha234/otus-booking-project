<?php

namespace App\Tests\Integration\Booking\Command;

use App\Booking\Application\Command\CreateResource\CreateResourceCommand;
use App\Booking\Application\Command\CreateResource\CreateResourceCommandHandler;
use App\Booking\Application\Exception\ResourceExistsException;
use App\Booking\Domain\Entity\Group;
use App\Booking\Domain\Repository\ResourceRepositoryInterface;
use App\Booking\Infrastructure\DataFixtures\GroupFixture;
use App\Shared\Application\Exception\NotFoundInRepositoryException;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

class CreateResourceTest extends WebTestCase
{
    private Generator $faker;
    private CreateResourceCommandHandler $handler;
    private ResourceRepositoryInterface $resourceRepository;
    private ORMExecutor $executor;
    private EntityManagerInterface $entityManager;

    public function setUp(): void
    {
        parent::setUp();
        $container = self::getContainer();

        $this->faker = Factory::create();
        $this->handler = $container->get(CreateResourceCommandHandler::class);
        $this->resourceRepository = $container->get(ResourceRepositoryInterface::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);

        $loader = new Loader();
        // We need a user (for the group) and a group for our tests
        $loader->addFixture($container->get(UserFixtures::class));
        $loader->addFixture($container->get(GroupFixture::class));

        $this->executor = new ORMExecutor($this->entityManager, new ORMPurger());
        $this->executor->execute($loader->getFixtures());
    }

    public function test_should_create_resource(): void
    {
        // Arrange
        /** @var Group $group */
        $group = $this->executor->getReferenceRepository()->getReference('group', Group::class);

        $resourceName = $this->faker->bs();
        $quantity = $this->faker->numberBetween(1, 10);

        $command = new CreateResourceCommand($group->getId(), $resourceName, $quantity);

        // Act
        $resourceId = ($this->handler)($command);

        // Assert
        $this->entityManager->clear();
        $resource = $this->resourceRepository->getById($resourceId);

        $this->assertNotNull($resource);
        $this->assertEquals($resourceId, $resource->getId());
        $this->assertEquals($resourceName, $resource->getName());
        $this->assertEquals($quantity, $resource->getQuantity());
        $this->assertEquals($group->getId(), $resource->getGroup()->getId());
        $this->assertTrue($resource->isActive());
    }

    public function test_should_throw_exception_when_group_not_found(): void
    {
        // Arrange
        $nonExistentGroupId = Uuid::v4()->toRfc4122();
        $command = new CreateResourceCommand($nonExistentGroupId, 'Some Resource', 5);

        // Assert
        $this->expectException(NotFoundInRepositoryException::class);
        $this->expectExceptionMessage('Group not found');

        // Act
        ($this->handler)($command);
    }

    public function test_should_throw_exception_when_resource_already_exists(): void
    {
        // Arrange
        /** @var Group $group */
        $group = $this->executor->getReferenceRepository()->getReference('group', Group::class);
        $resourceName = 'Existing Conference Room';

        // Create the first resource
        $firstCommand = new CreateResourceCommand($group->getId(), $resourceName, 1);
        ($this->handler)($firstCommand);

        // Prepare to create a second one with the same name in the same group
        $secondCommand = new CreateResourceCommand($group->getId(), $resourceName, 5);

        // Assert
        $this->expectException(ResourceExistsException::class);
        $this->expectExceptionMessage('This resource already exists');

        // Act
        ($this->handler)($secondCommand);
    }
}
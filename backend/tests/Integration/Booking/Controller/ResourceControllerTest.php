<?php

namespace App\Tests\Integration\Booking\Controller;

use App\User\Domain\Entity\User;
use App\Booking\Domain\Entity\Group;
use App\Booking\Domain\Entity\Resource;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use App\User\Infrastructure\DataFixtures\SecondUserFixtures;
use App\Booking\Infrastructure\DataFixtures\GroupFixture;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResourceControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private ?ORMExecutor $executor = null;
    private ?EntityManagerInterface $entityManager = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);

        $loader = new Loader();
        $loader->addFixture($container->get(UserFixtures::class));
        $loader->addFixture($container->get(SecondUserFixtures::class));
        $loader->addFixture($container->get(GroupFixture::class));

        $this->executor = new ORMExecutor($this->entityManager, new ORMPurger());
        $this->executor->execute($loader->getFixtures());
    }

    public function test_resource_routes_are_protected_from_anonymous_users(): void
    {
        /** @var Group $group */
        $group = $this->executor->getReferenceRepository()->getReference('group', Group::class);

        $this->client->request('GET', '/booking-group/' . $group->getId() . '/resource/create');
        $this->assertResponseRedirects('http://localhost/login');

        $this->client->request('POST', '/booking-group/' . $group->getId() . '/resource/create');
        $this->assertResponseRedirects('http://localhost/login');
    }

    public function test_create_page_is_accessible_for_logged_in_user_and_group_owner(): void
    {
        // Arrange
        /** @var User $user */
        $user = $this->executor->getReferenceRepository()->getReference('user', User::class);
        $this->client->loginUser($user);

        /** @var Group $group */
        $group = $this->executor->getReferenceRepository()->getReference('group', Group::class);

        // Act
        $this->client->request('GET', '/booking-group/' . $group->getId() . '/resource/create');

        // Assert
        $this->assertResponseIsSuccessful();
    }

    public function test_create_page_is_not_accessible_for_logged_in_user_but_not_group_owner(): void
    {
        // Arrange
        /** @var User $secondUser */
        $secondUser = $this->executor->getReferenceRepository()->getReference('second_user', User::class);
        $this->client->loginUser($secondUser);

        /** @var Group $group */
        $group = $this->executor->getReferenceRepository()->getReference('group', Group::class);

        // Act
        $this->client->catchExceptions(false);
        $this->expectException(NotFoundHttpException::class);
        $this->client->request('GET', '/booking-group/' . $group->getId() . '/resource/create');

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_can_create_resource(): void
    {
        // Arrange
        /** @var User $user */
        $user = $this->executor->getReferenceRepository()->getReference('user', User::class);
        $this->client->loginUser($user);

        /** @var Group $group */
        $group = $this->executor->getReferenceRepository()->getReference('group', Group::class);

        // Act
        $this->client->request(
            'POST',
            '/booking-group/' . $group->getId() . '/resource/create',
            [
                'name' => 'Test Resource',
                'quantity' => 5,
            ]
        );

        // Assert
        $this->assertResponseRedirects('/booking-group/' . $group->getId());
        $this->client->followRedirect();
        $this->assertSelectorTextContains('body', 'Resource created successfully!');

        $resource = $this->entityManager->getRepository(Resource::class)->findOneBy([
            'name' => 'Test Resource',
            'quantity' => 5,
        ]);

        $this->assertNotEmpty($resource);
    }

    public function test_create_resource_with_invalid_data(): void
    {
        // Arrange
        /** @var User $user */
        $user = $this->executor->getReferenceRepository()->getReference('user', User::class);
        $this->client->loginUser($user);

        /** @var Group $group */
        $group = $this->executor->getReferenceRepository()->getReference('group', Group::class);

        // Act
        $this->client->request(
            'POST',
            '/booking-group/' . $group->getId() . '/resource/create',
            [
                'name' => '', // Invalid: name is blank
                'quantity' => -1, // Invalid: quantity is negative
            ]
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertSelectorTextContains('body', 'This value is too short.');
        $this->assertSelectorTextContains('body', 'This value should be greater than 0.');
    }

    public function test_create_resource_with_existing_name(): void
    {
        // Arrange
        /** @var User $user */
        $user = $this->executor->getReferenceRepository()->getReference('user', User::class);
        $this->client->loginUser($user);

        /** @var Group $group */
        $group = $this->executor->getReferenceRepository()->getReference('group', Group::class);

        // Act
        // First, create a resource
        $this->client->request('POST', '/booking-group/' . $group->getId() . '/resource/create', [
            'name' => 'Test Resource',
            'quantity' => 5,
        ]);
        $this->assertResponseRedirects('/booking-group/' . $group->getId());

        // Then, try to create another resource with the same name
        $this->client->request('POST', '/booking-group/' . $group->getId() . '/resource/create', [
            'name' => 'Test Resource',
            'quantity' => 10,
        ]);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertSelectorTextContains('body', 'Resource with given name already exists in this group');
    }
}
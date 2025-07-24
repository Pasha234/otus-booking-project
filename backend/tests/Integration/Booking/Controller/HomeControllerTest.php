<?php

namespace App\Tests\Integration\Booking\Controller;

use App\User\Domain\Entity\User;
use App\Booking\Domain\Entity\Group;
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

class HomeControllerTest extends WebTestCase
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

    public function test_home_page_is_accessible_for_logged_in_user(): void
    {
        // Arrange
        /** @var User $user */
        $user = $this->executor->getReferenceRepository()->getReference('user', User::class);
        $this->client->loginUser($user);

        // Act
        $this->client->request('GET', '/');

        // Assert
        $this->assertResponseIsSuccessful();
    }

    public function test_home_page_displays_booking_groups_for_logged_in_user(): void
    {
        // Arrange
        /** @var User $user */
        $user = $this->executor->getReferenceRepository()->getReference('user', User::class);
        /** @var Group $group */
        $group = $this->executor->getReferenceRepository()->getReference('group', Group::class);
        $this->client->loginUser($user);

        // Act
        $this->client->request('GET', '/');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($group->getName(), $this->client->getResponse()->getContent());
    }
}
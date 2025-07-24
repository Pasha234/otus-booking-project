<?php

namespace App\Tests\Integration\Booking\Controller;

use App\User\Domain\Entity\User;
use App\Booking\Domain\Entity\Group;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use App\User\Infrastructure\DataFixtures\SecondUserFixtures;
use App\Booking\Infrastructure\DataFixtures\GroupFixture;

class BookingGroupControllerTest extends WebTestCase
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

    public function test_group_routes_are_protected_from_anonymous_users(): void
    {
        $this->client->request('GET', '/booking-group/create');
        $this->assertResponseRedirects('http://localhost/login');

        $this->client->request('POST', '/booking-group/create');
        $this->assertResponseRedirects('http://localhost/login');

        $this->client->request('GET', '/booking-group/some-id');
        $this->assertResponseRedirects('http://localhost/login');

        $this->client->request('GET', '/booking-group/some-id/settings');
        $this->assertResponseRedirects('http://localhost/login');

        $this->client->request('GET', '/booking-group/some-id/search-users');
        $this->assertResponseRedirects('http://localhost/login');

        $this->client->request('POST', '/booking-group/some-id/invite');
        $this->assertResponseRedirects('http://localhost/login');
    }

    public function test_create_page_is_accessible_for_logged_in_user(): void
    {
        // Arrange
        /** @var User $user */
        $user = $this->executor->getReferenceRepository()->getReference('user', User::class);
        $this->client->loginUser($user);

        // Act
        $this->client->request('GET', '/booking-group/create');

        // Assert
        $this->assertResponseIsSuccessful();
    }

    public function test_can_create_group(): void
    {
        // Arrange
        /** @var User $user */
        $user = $this->executor->getReferenceRepository()->getReference('user', User::class);
        $this->client->loginUser($user);

        // Act
        $crawler = $this->client->request('POST', '/booking-group/create', [
            'name' => 'Test Group',
            'description' => 'Test Description',
        ]);

        // Assert
        $this->assertResponseRedirects('/');

        // Assert database state
        // $this->entityManager->clear(); // Clear entity manager to fetch from DB
        // $groupRepository = $this->entityManager->getRepository(Group::class);
        // $group = $groupRepository->findOneBy(['name' => 'Test Group']);
        // $this->assertNotNull($group);
        // $this->assertEquals('Test Description', $group->getDescription());
        // $this->assertEquals($user->getId(), $group->getOwner()->getId());
    }

    public function test_show_page_is_accessible_for_logged_in_user(): void
    {
        // Arrange
        /** @var User $user */
        $user = $this->executor->getReferenceRepository()->getReference('user', User::class);
        $this->client->loginUser($user);

        /** @var Group $bookingGroup */
        $bookingGroup = $this->executor->getReferenceRepository()->getReference('group', Group::class);

        // Act
        $crawler = $this->client->request('GET', '/booking-group/' . $bookingGroup->getId());

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', $bookingGroup->getName());
    }

    public function test_settings_page_is_accessible_for_logged_in_user(): void
    {
        // Arrange
        /** @var User $user */
        $user = $this->executor->getReferenceRepository()->getReference('user', User::class);
        $this->client->loginUser($user);

        /** @var Group $bookingGroup */
        $bookingGroup = $this->executor->getReferenceRepository()->getReference('group', Group::class);

        // Act
        $this->client->request('GET', '/booking-group/' . $bookingGroup->getId() . '/settings');

        // Assert
        $this->assertResponseIsSuccessful();
    }

    public function test_search_users_endpoint_returns_results(): void
    {
        // Arrange
        /** @var User $user */
        $user = $this->executor->getReferenceRepository()->getReference('user', User::class);
        $this->client->loginUser($user);

        /** @var Group $bookingGroup */
        $bookingGroup = $this->executor->getReferenceRepository()->getReference('group', Group::class);

        // Act
        $this->client->request('GET', '/booking-group/' . $bookingGroup->getId() . '/search-users?q=user');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($responseContent);
    }

    public function test_invite_user_endpoint_sends_invitation(): void
    {
        // Arrange
        /** @var User $user */
        $user = $this->executor->getReferenceRepository()->getReference('user', User::class);
        $this->client->loginUser($user);

        /** @var Group $bookingGroup */
        $bookingGroup = $this->executor->getReferenceRepository()->getReference('group', Group::class);

        /** @var User $secondUser */
        $secondUser = $this->executor->getReferenceRepository()->getReference('second_user', User::class);

        // Act
        $this->client->request('POST', '/booking-group/' . $bookingGroup->getId() . '/invite', [
            'email' => $secondUser->getEmail(),
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString('{"message":"Invitation sent successfully."}', $this->client->getResponse()->getContent());

        //  Assert database state
        // $this->entityManager->clear();
        // $invitationRepository = $this->entityManager->getRepository(Invitation::class);
        // $invitation = $invitationRepository->findOneBy(['email' => $secondUser->getEmail(), 'group' => $bookingGroup]);
        // $this->assertNotNull($invitation);
    }

    public function test_invite_user_endpoint_returns_error_if_user_not_found(): void
    {
        // Arrange
        /** @var User $user */
        $user = $this->executor->getReferenceRepository()->getReference('user', User::class);
        $this->client->loginUser($user);

        /** @var Group $bookingGroup */
        $bookingGroup = $this->executor->getReferenceRepository()->getReference('group', Group::class);

        // Act
        $this->client->request('POST', '/booking-group/' . $bookingGroup->getId() . '/invite', [
            'email' => 'nonexistent@example.com',
        ]);

        // Assert
        $this->assertResponseStatusCodeSame(404);
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('User to invite not found', $responseContent['error']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // avoid memory leaks
        if ($this->entityManager) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
        $this->executor = null;
    }
}
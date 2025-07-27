<?php

namespace App\Tests\Integration\Booking\Controller;

use App\User\Domain\Entity\User;
use App\Booking\Domain\Entity\Group;
use Doctrine\Common\DataFixtures\Loader;
use Symfony\Component\HttpFoundation\Response;
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

    public function test_owner_can_remove_user_from_group(): void
    {
        // Arrange
        /** @var User $owner */
        $owner = $this->executor->getReferenceRepository()->getReference('user', User::class);
        $this->client->loginUser($owner);

        /** @var User $memberToRemove */
        $memberToRemove = $this->executor->getReferenceRepository()->getReference('second_user', User::class);

        /** @var Group $bookingGroup */
        $bookingGroup = $this->executor->getReferenceRepository()->getReference('group', Group::class);

        // Ensure the user to be removed is actually a member first
        $bookingGroup->addUserAsParticipant($memberToRemove);
        $this->entityManager->persist($bookingGroup);
        $this->entityManager->flush();

        // Act
        $this->client->request(
            'POST',
            '/booking-group/' . $bookingGroup->getId() . '/remove_user',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['user_id' => $memberToRemove->getId()])
        );

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString(
            '{"message":"Participant was deleted successfully."}',
            $this->client->getResponse()->getContent()
        );

        // Assert database state
        $this->entityManager->clear();
        /** @var Group $updatedGroup */
        $updatedGroup = $this->entityManager->find(Group::class, $bookingGroup->getId());
        $this->assertNotNull($updatedGroup);
        $this->assertCount(1, $updatedGroup->getGroupParticipants()); // Should only be the owner left
    }

    public function test_non_owner_cannot_remove_user_from_group(): void
    {
        // Arrange
        /** @var User $member */
        $member = $this->executor->getReferenceRepository()->getReference('second_user', User::class);

        /** @var Group $bookingGroup */
        $bookingGroup = $this->executor->getReferenceRepository()->getReference('group', Group::class);

        // Add the second user as a member
        $bookingGroup->addUserAsParticipant($member);
        $this->entityManager->persist($bookingGroup);
        $this->entityManager->flush();

        // Login as the non-owner member
        $this->client->loginUser($member);

        // Act: Try to remove the owner
        $this->client->request(
            'POST',
            '/booking-group/' . $bookingGroup->getId() . '/remove_user',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['user_id' => $bookingGroup->getOwner()->getId()])
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function test_remove_user_returns_validation_error_if_user_id_is_missing(): void
    {
        // Arrange
        /** @var User $owner */
        $owner = $this->executor->getReferenceRepository()->getReference('user', User::class);
        $this->client->loginUser($owner);

        /** @var Group $bookingGroup */
        $bookingGroup = $this->executor->getReferenceRepository()->getReference('group', Group::class);

        // Act
        $this->client->request(
            'POST',
            '/booking-group/' . $bookingGroup->getId() . '/remove_user',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([]) // Empty payload
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('This value should not be blank.', $responseContent['form_errors']['user_id']);
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
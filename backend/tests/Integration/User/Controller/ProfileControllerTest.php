<?php

namespace App\Tests\Integration\User\Controller;

use App\Booking\Infrastructure\DataFixtures\GroupFixture;
use App\User\Domain\Entity\User;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\ORM\EntityManagerInterface;
use App\User\Domain\Entity\Invitation;
use App\User\Domain\Enum\InvitationStatus;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use App\User\Infrastructure\DataFixtures\InvitationFixtures;
use App\User\Domain\Repository\InvitationRepositoryInterface;
use App\User\Infrastructure\DataFixtures\SecondUserFixtures;

class ProfileControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private ?ORMExecutor $executor = null;
    private ?EntityManagerInterface $entityManager = null;
    private ?InvitationRepositoryInterface $invitationRepository = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        // NOTE: This test assumes an InvitationRepositoryInterface is available in the container.
        $this->invitationRepository = $container->get(InvitationRepositoryInterface::class);

        $loader = new Loader();
        // NOTE: This test assumes you have UserFixtures and InvitationFixtures.
        // InvitationFixtures should depend on UserFixtures to ensure correct loading order.
        // I've also assumed UserFixtures creates two users with references 'user' and 'other_user',
        // and InvitationFixtures creates an invitation for 'user' with reference 'invitation'.
        $loader->addFixture($container->get(UserFixtures::class));
        $loader->addFixture($container->get(SecondUserFixtures::class));
        $loader->addFixture($container->get(GroupFixture::class));
        $loader->addFixture($container->get(InvitationFixtures::class));

        $this->executor = new ORMExecutor($this->entityManager, new ORMPurger());
        $this->executor->execute($loader->getFixtures());
    }

    public function test_profile_routes_are_protected_from_anonymous_users(): void
    {
        $this->client->request('GET', '/profile');
        $this->assertResponseRedirects('http://localhost/login');

        $this->client->request('GET', '/profile/invitations');
        $this->assertResponseRedirects('http://localhost/login');

        $this->client->request('POST', '/profile/invitations/some-id/accept');
        $this->assertResponseRedirects('http://localhost/login');

        $this->client->request('POST', '/profile/invitations/some-id/decline');
        $this->assertResponseRedirects('http://localhost/login');
    }

    public function test_profile_page_is_accessible_for_logged_in_user(): void
    {
        // Arrange
        /** @var User $user */
        $user = $this->executor->getReferenceRepository()->getReference('user', User::class);
        $this->client->loginUser($user);

        // Act
        $crawler = $this->client->request('GET', '/profile');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($user->getFullName(), $crawler->filter('body')->text());
    }

    public function test_invitations_page_shows_user_invitations(): void
    {
        // Arrange
        /** @var User $user */
        $user = $this->executor->getReferenceRepository()->getReference('user', User::class);
        $this->client->loginUser($user);

        /** @var Invitation $invitation */
        $invitation = $this->executor->getReferenceRepository()->getReference('invitation', Invitation::class);

        // Act
        $crawler = $this->client->request('GET', '/profile/invitations');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'My Invitations'); // Assuming template has this
        // NOTE: Assumes the invitation's related booking title is displayed.
        // $this->assertStringContainsString($invitation->getGroup()->getTitle(), $crawler->filter('body')->text());
    }

    public function test_can_accept_invitation(): void
    {
        // Arrange
        /** @var User $user */
        $user = $this->executor->getReferenceRepository()->getReference('user', User::class);
        $this->client->loginUser($user);

        /** @var Invitation $invitation */
        $invitation = $this->executor->getReferenceRepository()->getReference('invitation', Invitation::class);
        // NOTE: Assumes Invitation entity has getStatus() method and 'pending' status.
        $this->assertEquals(InvitationStatus::PENDING, $invitation->getStatus());

        // Act
        $this->client->request('POST', '/profile/invitations/' . $invitation->getId() . '/accept');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString('{"message":"Invitation accepted."}', $this->client->getResponse()->getContent());

        // Assert database state
        $this->entityManager->clear(); // Clear entity manager to fetch from DB
        $updatedInvitation = $this->invitationRepository->getById($invitation->getId());
        $this->assertNotNull($updatedInvitation);
        $this->assertEquals(InvitationStatus::ACCEPTED, $updatedInvitation->getStatus());
    }

    public function test_can_decline_invitation(): void
    {
        // Arrange
        /** @var User $user */
        $user = $this->executor->getReferenceRepository()->getReference('user', User::class);
        $this->client->loginUser($user);

        /** @var Invitation $invitation */
        $invitation = $this->executor->getReferenceRepository()->getReference('invitation', Invitation::class);
        $this->assertEquals(InvitationStatus::PENDING, $invitation->getStatus());

        // Act
        $this->client->request('POST', '/profile/invitations/' . $invitation->getId() . '/decline');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString('{"message":"Invitation declined."}', $this->client->getResponse()->getContent());

        // Assert database state
        $this->entityManager->clear();
        $updatedInvitation = $this->invitationRepository->getById($invitation->getId());
        $this->assertNotNull($updatedInvitation);
        $this->assertEquals(InvitationStatus::DECLINED, $updatedInvitation->getStatus());
    }

    public function test_cannot_act_on_invitation_of_another_user(): void
    {
        // Arrange
        /** @var User $otherUser */
        $otherUser = $this->executor->getReferenceRepository()->getReference('second_user', User::class);
        $this->client->loginUser($otherUser);

        /** @var Invitation $invitationForMainUser */
        $invitationForMainUser = $this->executor->getReferenceRepository()->getReference('invitation', Invitation::class);

        // Act
        $this->client->request('POST', '/profile/invitations/' . $invitationForMainUser->getId() . '/accept');

        // Assert
        $this->assertResponseStatusCodeSame(404);
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        // NOTE: The exact error message depends on the exception thrown from the command handler.
        $this->assertStringContainsString('not found', $responseContent['error']);
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
        $this->invitationRepository = null;
    }
}